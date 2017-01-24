/*
 * Sendmail milter to do stuff based on database-held rules
 * based on http://serverfault.com/questions/131730/configure-sendmail-to-clone-all-outgoing-email
 * (C) 2016-2017 Garry Glendown / fleximilter@mlfwd.de
 */

#include <stdio.h>
#include <stdarg.h>
#include <stdlib.h>
#include <sysexits.h>
#include <unistd.h>
#include <sys/stat.h>
#include <syslog.h>
#include <time.h>
#include <pthread.h>

#include "libmilter/mfapi.h"
#include "libmilter/mfdef.h"

#include "flexi.h"
#include "AUTH.h"

#ifndef bool
# define bool   int
# define TRUE   1
# define FALSE  0
#endif /* ! bool */

#include <my_global.h>
#include <mysql.h>
#include <m_string.h>

#define GETPRIV priv=(struct flexiPriv *)smfi_getpriv(ctx);
#define LOG

// #define WRITELOG	1

#ifdef WRITELOG
FILE *fh;
#endif

//MYSQL *my,mys;

pthread_mutex_t lock;
int mutexinit=0;

// some function declarations
void cleanup();

#define EMAILLEN	256
struct rcpt {			// linked list of recipients
    struct rcpt *next;
    char email[EMAILLEN];
    char action;		// what to do with this recipient
    char arg[EMAILLEN];		// forward/replacement address
};

#define MAXHEADERV 2000
struct hdrs {
    struct hdrs *next;
    char headerf[64];		// headerfield
    char headerv[MAXHEADERV];	// header content
    struct tmpstr *hdrf,*hdrv;	// storage of escaped values
    char action;
    char headervold[MAXHEADERV];// old value for replacement
};

struct tmpstr {
    size_t	len;
    char	str[0];		// scaled by malloc
};

struct flexiPriv {
	// MySQL handler
	struct tmpstr *envfrom;	// envelope from address
	sfsistat rv;		// return value
	MYSQL *my;		// MySQL Pointer
	struct rcpt *rcpt;	// List of envelope recipients
	struct hdrs *hdrs;	// List of additional headers
	struct hdrs *mlhd;	// Message header lines
	struct tmpstr *myq;	// buffer for temporary use, mysqlquery
	struct tmpstr *tmp;	// buffer for temporary use
};

struct tmpstr *scaleTmp(str,len)
    struct tmpstr *str;
    size_t len;
{
    if (str) {
//	fprintf(stderr,"scaleTmp %ld->%ld",str->len,len);
	if (len>str->len) {
	    len=(len+1024)&0xfffffc00;
	    str=realloc(str,len+sizeof(struct tmpstr));
	    if (str) str->len=len;
	}
    }
    else {
//	fprintf(stderr,"scaleTmp %ld->%ld",0l,len);
	len=(len+1024)&0xfffffc00;
	str=malloc(len+sizeof(struct tmpstr));
	if (str)
	    str->len=len,
	    memset(&(str->str),0,len);
    }
    //fprintf(stderr," - scaled\n");
    return(str);
}


void doLog(int pri,char *msg)
{
    openlog("milter-flexi",LOG_PID,LOG_USER);
    syslog(pri,"%s",(char *)msg);
    closelog();
}

MYSQL *openMysql()
{
    MYSQL *my;
	// TODO do we need Mutex for calling the mysql_init? pointer goes into private, thread-specific memory, so not needed?!
	//
    if (!(my=mysql_init(NULL))) {
	fprintf(stderr,"mysql_init failed\n");
	doLog(LOG_CRIT,"mysql_init failed");
	exit(EX_SOFTWARE);
    }

    // TODO: move parameters to config file or command line options
    if (!(mysql_real_connect(my,DBSRV,DBUSR,DBPW,DBNAME,0,NULL,0))) {
	fprintf(stderr,"%s\n",mysql_error(my));
	doLog(LOG_CRIT,(char *)mysql_error(my));
	cleanup();
	exit(EX_SOFTWARE);
    }
    return my;
}

struct hdrs *newHdr(struct flexiPriv *priv,struct hdrs *hdrs,char *hf,char*hv)
{
    struct hdrs *h;
    char dummy[]="\0";
    if (!hv) hv=dummy;				// empty string pointer? TODO: can this even happen?

    if (!hdrs) {				// list doesn't exist, create
	hdrs=malloc(sizeof(struct hdrs));
	if (!hdrs) return(NULL);		// malloc failed
	h=hdrs;
    }
    else {
	h=hdrs;
	while (h->next) h=h->next;
	h->next=malloc(sizeof(struct hdrs));
	if (!h->next) return(hdrs);			// TODO: should communicate some error state
	h=h->next;
    }
    memset(h,0,sizeof(struct hdrs));
    strncpy((char *)&(h->headerf),hf,64);
    strncpy((char *)&(h->headerv),hv,MAXHEADERV);
    strncpy((char *)&(h->headervold),hv,MAXHEADERV);	// store original TODO: currently unused
    h->hdrf=scaleTmp(NULL,strlen(hf)*4);
    h->hdrv=scaleTmp(NULL,strlen(hv)*4);
    mysql_real_escape_string_quote(priv->my,h->hdrf->str,hf,strlen(hf),'\'');
    mysql_real_escape_string_quote(priv->my,h->hdrv->str,hv,strlen(hv),'\'');
    return(hdrs);
}

struct rcpt *newRcpt(struct rcpt *rcpt,char *email)
{
    struct rcpt *r;

#ifdef DEBUG
    fprintf(stderr,"RCPT: %s\n",email);
#endif
    if (!rcpt) {				// list doesn't exist, create
	rcpt=malloc(sizeof(struct rcpt));
	if (!rcpt) return(NULL);		// malloc failed
	memset(rcpt,0,sizeof(struct rcpt));
	strncpy((char *)&(rcpt->email),email,EMAILLEN);
	return(rcpt);
    }
    r=rcpt;
    while (r->next) r=r->next;
//fprintf(stderr,"Adding %s after %s\n",email,r->email);
    r->next=malloc(sizeof(struct rcpt));
    if (!r->next) return(rcpt);			// TODO: should communicate some error state
    r=r->next;
    memset(r,0,sizeof(struct rcpt));
    strncpy((char *)&(r->email),email,EMAILLEN);
    return(rcpt);
}

int getData(MYSQL *my,char *query,MYSQL_RES **res)
{
int r;
#ifdef DEBUG
    fprintf(stderr,"Query: %s (%lx)\n",query,(long)my);
#endif
    if (mysql_query(my,query)) {
	syslog(LOG_ERR,"Query failed, retrying: %s",mysql_error(my));
	fprintf(stderr,"Query failed, retrying: %s\n",mysql_error(my));
	//mysql_close(my); // did MySQL fail? double free if failed, commented out
	my=NULL;
	openMysql();
	if (mysql_query(my,query)) {
	    syslog(LOG_ERR,"Query failed, not retrying: %s",mysql_error(my));
	    fprintf(stderr,"Query failed, not retrying: %s\n",mysql_error(my));
	    return(-1);
	}
    }
    *res=mysql_store_result(my);
#ifdef DEBUG
    fprintf(stderr,"Got data ...\n");
#endif
    r=mysql_num_rows(*res);
//    if (r==0)
//	mysql_free_result(*res),*res=NULL;
    return(r);
}

char *tmpfmt(struct flexiPriv *priv, char *fmt, ...)
{
    va_list args;
    size_t qlen;
    char dummy;

    va_start(args,fmt);
    if (priv->tmp)
	qlen=vsnprintf(priv->tmp->str,priv->tmp->len,fmt,args);
    else
	qlen=vsnprintf(&dummy,0,fmt,args);
    if (qlen>(priv->tmp?priv->tmp->len:0)) {
	priv->tmp=scaleTmp(priv->tmp,qlen);
	if (priv->tmp) {
	    va_end(args);
	    va_start(args,fmt);
	    qlen=vsnprintf(priv->tmp->str,priv->tmp->len,fmt,args);
	}
	else {
	    doLog(LOG_CRIT,"malloc failed for tmp");
	    return(NULL);
	}
    }
    va_end(args);
    return(priv->tmp->str);
}

char *myquery(struct flexiPriv *priv, char *fmt, ...)
{
    va_list args;
    size_t qlen;
    char dummy;

#ifdef DEBUG
    fprintf(stderr,"MyQuery: starting for %s\n",fmt);
#endif
    va_start(args,fmt);
    if (priv->myq) {
#ifdef DEBUG
	fprintf(stderr,"Using previous query buffer len %ld\n",priv->myq->len);
#endif
	qlen=vsnprintf(priv->myq->str,priv->myq->len,fmt,args);
#ifdef DEBUG
	fprintf(stderr,"Formatting done ... %ld\n",qlen);
#endif
    }
    else {
#ifdef DEBUG
	fprintf(stderr,"New query buffer needed - ");
#endif
	qlen=vsnprintf(&dummy,0,fmt,args);
#ifdef DEBUG
	fprintf(stderr,"determined %ld bytes\n",qlen);
#endif
    }
    if (qlen>(priv->myq?priv->myq->len:0)) {
#ifdef DEBUG
	fprintf(stderr,"Need to extend query buffer\n");
#endif
	qlen=qlen+3072;		// extend allocation in 4k steps (scaleTmp adds 1k)
	priv->myq=scaleTmp(priv->myq,qlen);
	if (priv->myq) {
	    va_end(args);
	    va_start(args,fmt);
#ifdef DEBUG
	    fprintf(stderr,"Formatting ... ");
#endif
	    qlen=vsnprintf(priv->myq->str,priv->myq->len,fmt,args);
#ifdef DEBUG
	    fprintf(stderr,"done ... \n");
#endif
	}
	else {
	    doLog(LOG_CRIT,"malloc failed for myq");
	    return(NULL);
	}
    }
    else {
#ifdef DEBUG
	fprintf(stderr,"initial formatting sufficient\n");
#endif
    }
    va_end(args);
#ifdef DEBUG
    fprintf(stderr,"myquery finished\n");
#endif
    return(priv->myq->str);
}

int writeLog(struct flexiPriv *priv)
{
    return(0);
}

size_t safe_strlen(char *str)
{
    if (str)
	return(strlen(str));
    else
	return(0);
}

sfsistat
tmfi_connect(ctx,hostname,hostaddr)
	SMFICTX *ctx;
	char	*hostname;
	_SOCK_ADDR *hostaddr;
{
    	struct sockaddr_in *s4;
//    	struct sockaddr_in6 *s6;
	char *myqp;
	MYSQL_RES *res;
	int rows;
	MYSQL_ROW row;
	sfsistat rv=0;
	struct flexiPriv *priv;

	res=NULL;
#ifdef DEBUG
	fprintf(stderr,"Connect from %s / %d\n",hostname,hostaddr->sa_family);
	fprintf(stderr,"%ld\n",time(NULL));
#endif

	if (!smfi_getpriv(ctx)) {	// smfi_connect ought to be the first call to this milter, so should ALWAYS be undefined // TODO?
	    priv=malloc(sizeof(*priv));
	    if (!priv) {
		doLog(LOG_ERR,"Priv Mem Alloc failed");
		return(SMFIS_TEMPFAIL);
	    }
	    memset(priv,0,sizeof(*priv));
	    priv->rv=SMFIS_CONTINUE;
	    smfi_setpriv(ctx,priv);
	    priv->my=openMysql();		// get DB link
	}

	if (hostaddr->sa_family==AF_INET) {	// v4 address
#ifdef WRITELOG
{
size_t s;
	fputc((int)'c',fh);
	fwrite(&priv,sizeof(void *),1,fh);
	s=strlen(hostname);
	fwrite(&s,sizeof(size_t),1,fh);
	fwrite(hostname,strlen(hostname),1,fh);
	s=sizeof(struct sockaddr_in);
	fwrite(&s,sizeof(size_t),1,fh);
	fwrite(hostaddr,sizeof(struct sockaddr_in),1,fh);
	fflush(fh);
}
#endif
	    s4=(struct sockaddr_in *)hostaddr;
	    s4->sin_addr.s_addr=htonl(s4->sin_addr.s_addr);
#ifdef DEBUG 
	    fprintf(stderr,"%x\n",s4->sin_addr.s_addr);
#endif
	    // let MySQL query do the heavy lifting - match IPv4 address in number pattern
	    // with netmask to the IPv4 of connection and get the first match when ordered
	    // by priority - IP matches if (PATTERN XOR IP) AND MASK = 0
	    // "more specific" is used! (i.e., smaller net on matches wins no matter
	    // what the priority of the rule)
	    myqp=myquery(priv,"select id, action, (patternnum^%u)&patternmask as pmatch from bwlist where field='4' and matchtype='B' having pmatch=0 order by patternmask desc, prio limit 1",s4->sin_addr.s_addr);

#ifdef DEBUG
	    fprintf(stderr,"%s\n",myqp);
#endif
	    rows=getData(priv->my,myqp,&res);
	    switch (rows) {
		case 0:	// no matching B/W list entry found
		    rv=SMFIS_CONTINUE;
		    break;;
		case -1:			// error during MySQL query
		    rv=SMFIS_TEMPFAIL;		// or continue?
		    break;;
		default:			// one result (due to "limit 1" ...)
		    {
			char myhv[MAXHEADERV];
		    row=mysql_fetch_row(res);
		    snprintf(myhv,MAXHEADERV,
		    	"bwlist.id=%s/%s/%u.%u.%u.%u",row[0],row[1],
		    	(s4->sin_addr.s_addr&0xff000000)>>24,
		    	(s4->sin_addr.s_addr&0xff0000)>>16,
		    	(s4->sin_addr.s_addr&0xff00)>>8,
		    	(s4->sin_addr.s_addr&0xff));
		    priv->hdrs=newHdr(priv,priv->hdrs,"X-flexi-rule",myhv);
		    if (row[1][0]=='B') 	// blacklisted, reject connection
			priv->rv=rv=SMFIS_REJECT;
		    else if (row[1][0]=='W')	// accept unconditionally
			priv->rv=rv=SMFIS_ACCEPT;
		    else priv->rv=rv=SMFIS_CONTINUE;
		    }
	    }
	    if (res) {
#ifdef DEBUG
		fprintf(stderr,"Clearing mysql data\n");
#endif
		mysql_free_result(res);
		res=NULL;
	    }
	}
	else if (hostaddr->sa_family==AF_INET6) {	// v6 address - use MySQL IPv6 functions like INET6_ATON
	    //s6=(struct sockaddr_in6 *)hostaddr;
	    //for (int t=0;t<16;t++) fprintf(stderr,"%02x",s6->sin6_addr.s6_addr[t]);
	    //fprintf(stderr,"\n");
	    // TODO: Match IPv6 addresses!
	}
	else {
	    doLog(LOG_INFO,"Unexpected connection address family");
	    return(SMFIS_TEMPFAIL);		// maybe we shouldn't TEMPFAIL?
	}
	if (rv==0 || rv==SMFIS_CONTINUE) {	// continue checking, now hostname with pattern/regexp
	    //       if present. So if - at same priority - a blacklist entry matches, this counts
	    //       Same for Whitelist, and finally just continue checking
	    priv->tmp=scaleTmp(priv->tmp,4*strlen(hostname));	// make some room
	    mysql_real_escape_string_quote(priv->my,priv->tmp->str,hostname,strlen(hostname),'\'');
	    myqp=myquery(priv,"\
select id,action from bwlist where field='H' and\
 ((matchtype='P' and '%s' like pattern) or\
  (matchtype='R' and '%s' rlike pattern))  order by prio,action desc limit 1",
		&(priv->tmp->str),&(priv->tmp->str));

#ifdef DEBUG
	    fprintf(stderr,"%s\n",myqp);
#endif
	    rows=getData(priv->my,myqp,&res);
	    if (rows==1) {
		char myhv[MAXHEADERV];
		row=mysql_fetch_row(res);
		sprintf(myhv,"bwlist.id=%s/%s/%s",row[0],row[1],hostname);
		priv->hdrs=newHdr(priv,priv->hdrs,"X-flexi-rule",myhv);
		if (row[1][0]=='B') 	// blacklisted, reject connection
		    priv->rv=rv=SMFIS_REJECT;
		else if (row[1][0]=='W')	// accept unconditionally
		    priv->rv=rv=SMFIS_ACCEPT;
	    }
#ifdef DEBUG
	    fprintf(stderr,"finishing mysql\n");
#endif
	    if (res) mysql_free_result(res),res=NULL;
	}
	return((rv!=SMFIS_REJECT)?SMFIS_CONTINUE:rv);	// reject if blacklisted, otherwise continue
}

char *mysql_escape(MYSQL *my,char *str, char *dst)
{
    mysql_real_escape_string_quote(my,dst,str,strlen(str),'\'');
    return (dst);
}

sfsistat
tmfi_helo(ctx, helo)
	SMFICTX *ctx;
	char *helo;
{
    char *myqp;
    MYSQL_RES *res=NULL;
    int rows;
    MYSQL_ROW row;
    struct flexiPriv *priv;

    GETPRIV;
#ifdef DEBUG
    fprintf(stderr,"HELO: %s\n",helo);
#endif
#ifdef WRITELOG
{
size_t s;
	fputc((int)'e',fh);
	fwrite(&priv,sizeof(void *),1,fh);
	s=strlen(helo);
	fwrite(&s,sizeof(size_t),1,fh);
	fwrite(helo,strlen(helo),1,fh);
	fflush(fh);
}
#endif

    if (priv->rv==SMFIS_ACCEPT) return(SMFIS_CONTINUE);	// whitelisted, continue until body

    priv->tmp=scaleTmp(priv->tmp,4*strlen(helo));	// make some room
    mysql_real_escape_string_quote(priv->my,priv->tmp->str,helo,strlen(helo),'\'');
    myqp=myquery(priv,"\
select id,action from bwlist where field='E' and \
((matchtype='P' and '%s' like pattern) or \
(matchtype='R' and '%s' rlike pattern))  order by prio,action desc limit 1",
	&(priv->tmp->str),&(priv->tmp->str));
    rows=getData(priv->my,myqp,&res);
    if (rows==1) {
	char myhv[MAXHEADERV];
	row=mysql_fetch_row(res);
	sprintf(myhv,"bwlist.id=%s/%s/%s",row[0],row[1],helo);
	priv->hdrs=newHdr(priv,priv->hdrs,"X-flexi-rule",myhv);
	if (row[1][0]=='B') 	// blacklisted, reject connection
	    priv->rv=SMFIS_REJECT;
	else if (row[1][0]=='W')	// accept unconditionally
	    priv->rv=SMFIS_ACCEPT;
    }
    if (res) mysql_free_result(res),res=NULL;
    return((priv->rv!=SMFIS_REJECT)?SMFIS_CONTINUE:priv->rv);	// reject if blacklisted, otherwise continue
}

sfsistat
tmfi_close(ctx)
	SMFICTX *ctx;
{
    struct flexiPriv *priv;
    GETPRIV;
#ifdef WRITELOG
{
    fputc((int)'C',fh);
    fwrite(&priv,sizeof(void *),1,fh);
    fflush(fh);
}
#endif
#ifdef DEBUG
    fprintf(stderr,"Closing\n");
#endif
    // TODO: Cleanup contents of priv structure, mysql handle etc.
    if (priv) {
	if (priv->hdrs) {
	    struct hdrs *c,*r;
	    c=priv->hdrs;
	    while (c) {
		r=c->next;
		if (c->hdrf) free(c->hdrf);
		if (c->hdrv) free(c->hdrv);
		free((void *)c);
		c=r;
	    }
	}
	priv->hdrs=NULL;
	if (priv->mlhd) {
	    struct hdrs *c,*r;
	    c=priv->mlhd;
	    while (c) {
		r=c->next;
		if (c->hdrf) free(c->hdrf);
		if (c->hdrv) free(c->hdrv);
		free((void *)c);
		c=r;
	    }
	}
	priv->mlhd=NULL;
	if (priv->rcpt) {
	    struct rcpt *c,*r;
	    c=priv->rcpt;
	    while ((r=c->next)) {
		free((void *)c);
		c=r;
	    }
	    free((void *)c);
	}
	priv->rcpt=NULL;
	if (priv->myq) free(priv->myq);
	if (priv->tmp) free(priv->tmp);
	if (priv->envfrom) free(priv->envfrom);
	if (priv->my) mysql_close(priv->my),mysql_thread_end();
	free((void *)priv);
	priv=NULL;
    }
    smfi_setpriv(ctx,NULL);	// should not be necessary anymore ...
#ifdef DEBUG
    fprintf(stderr,"cleanup done\n");
#endif
    return(SMFIS_CONTINUE); //TODO: Optionally return TMPFAIL? <- why did I think that?
}

struct tmpstr *buildUHdrs(struct flexiPriv *priv,struct tmpstr *hdrl)
{
    struct hdrs *hdrs;
    struct uhdr {
	struct uhdr *next;
	char str[64];
    } *uhdr=NULL,*h;

    hdrs=priv->mlhd;
    hdrl=scaleTmp(hdrl,2000);
    while (hdrs) {
	h=uhdr;
	while (h) {
	    if (!(strcasecmp(h->str,hdrs->headerf)))
		break;
	    else
		h=h->next;
	}
	if (!h) {		// found a new header
	    h=malloc(sizeof(struct uhdr));
	    h->next=uhdr;	// if there was no previous header,
	    uhdr=h;		// there is now ;)
	    strncpy(h->str,hdrs->headerf,64);
	    // estimate new headerline length
	    hdrl=scaleTmp(hdrl,strlen(hdrl->str)+300);
	    if (strlen(hdrl->str)) strcat(hdrl->str,",");
	    strcat(hdrl->str,"'");
	    strcat(hdrl->str,hdrs->hdrf->str);
//	    mysql_real_escape_string_quote(my,&(hdrl->str[strlen(hdrl->str)]),
//	    	hdrs->headerf,strlen(hdrs->headerf),'\'');
	    strcat(hdrl->str,"'");
	}
	hdrs=hdrs->next;
    }
#ifdef DEBUG
    fprintf(stderr,"Headers: %s\n",hdrl->str);
#endif
    while (uhdr) {
	h=uhdr->next;
	free(uhdr);
	uhdr=h;
    }
    return(hdrl);
}

sfsistat
tmfi_eom(ctx)
     SMFICTX *ctx;
{
    char *myqp;
    MYSQL_RES *res,*res2;
    int rows,rows2;
    MYSQL_ROW row,row2;
    
    time_t now;

    struct flexiPriv *priv;
    struct rcpt *rcpt;
    struct tmpstr *subject=NULL,*sender=NULL,*hsender=NULL;
    struct hdrs *hdrs;
    struct tmpstr *hdrf=NULL,*hdrv=NULL;
    sfsistat rv=0;
    int t;
    int match=0;
    int rcpts=0;
    char tmp[256];
    GETPRIV;
#ifdef WRITELOG
{
	fputc((int)'M',fh);
	fwrite(&priv,sizeof(void *),1,fh);
	fflush(fh);
}
#endif
    now=time(NULL);				// get current timestamp
    hsender=scaleTmp(hsender,1);		// create headersender tmp string
    subject=scaleTmp(subject,10);
    subject->str[0]=0;
//    smfi_delrcpt(ctx,"garry@test.glendown.de");
//    smfi_addrcpt(ctx,"root@test.glendown.de");
    if (priv->rv!=SMFIS_REJECT) {	// message should not yet be rejected, process rules
	hdrs=priv->mlhd;
	while (hdrs) {
	    if (!strcasecmp(hdrs->headerf,"subject")) {
		subject=scaleTmp(subject,strlen(hdrs->headerv)*4);
		mysql_real_escape_string_quote(priv->my,subject->str,hdrs->headerv,strlen(hdrs->headerv),'\'');
		//fprintf(stderr,"Converted subject to mysql: %s\n",subject->str);
	    }
	    // process the "from:" header as additional sender address
	    if (!strcasecmp(hdrs->headerf,"from")) {
		hsender=scaleTmp(hsender,strlen(hdrs->headerv)*8+80);
		sender=scaleTmp(sender,strlen(hdrs->headerv)*4);
		mysql_real_escape_string_quote(priv->my,sender->str,hdrs->headerv,strlen(hdrs->headerv),'\'');
		if (strcasecmp(priv->envfrom->str,sender->str)) {	// different from envelope?
		    snprintf(hsender->str,hsender->len,
		    "(('%s' like concat('%%',sender,'%%') and sendertype='P') or ('%s' rlike sender and sendertype='R')) or ",
		    sender->str,sender->str);
		}
	    }
	    hdrs=hdrs->next;
	}
	hdrf=buildUHdrs(priv,hdrf);		// hdrf->str contains finished list of header fields for sql "in (...)" command
	// do this for all recipients
	rcpt=priv->rcpt;
	while (rcpt) {
#ifdef DEBUG
	    fprintf(stderr,"Processing recipient %s\n",rcpt->email);
#endif
	    priv->tmp=scaleTmp(priv->tmp,strlen(rcpt->email)*4);
	    mysql_real_escape_string_quote(priv->my,priv->tmp->str,rcpt->email,strlen(rcpt->email),'\'');
	    myqp=myquery(priv,"\
select * from filter where (endts=0 or endts>=%lu) and \
(%s (('%s' like concat('%%',sender,'%%') and sendertype='P') or ('%s' rlike sender and sendertype='R'))) and \
(('%s' like concat('%%',rcpt,'%%') and rcpttype='P') or ('%s' rlike rcpt and rcpttype='R')) and \
(('%s' like concat('%%',subject,'%%') and subjecttype='P') or ('%s' rlike subject and subjecttype='R')) and \
(headerf in (%s,'') or headerf is NULL) order by prio",now,hsender->str,priv->envfrom->str,priv->envfrom->str,
	    priv->tmp->str,priv->tmp->str,subject->str,subject->str,hdrf->str);
	    //fprintf(stderr,"%s\n",myqp);
	    rows=getData(priv->my,myqp,&res);
	    //fprintf(stderr,"%s\n%d rows\n",myqp,rows);
	    for (t=0,match=0;(t<rows) && !match;t++) {			// process preliminarily matching filters
		row=mysql_fetch_row(res);
		if (safe_strlen(row[FILT_headerf])>0) {			// process additional header value?
		    hdrv=scaleTmp(hdrv,safe_strlen(row[FILT_headerv])*4);
		    mysql_real_escape_string_quote(priv->my,hdrv->str,row[FILT_headerv],safe_strlen(row[FILT_headerv]),'\'');
		    hdrs=priv->mlhd;
		    while (hdrs && !match) {
			if (!(strcasecmp(hdrs->headerf,row[FILT_headerf]))) {	// header matches
			    switch (row[FILT_headertype][0]) {
				case 'P':
				    myqp=myquery(priv,"select '%s' like '%s'",hdrs->hdrv->str,hdrv->str);
				    break;;
				case 'R':
				    myqp=myquery(priv,"select '%s' rlike '%s'",hdrs->hdrv->str,hdrv->str);
				    break;;
			    }
			    rows2=getData(priv->my,myqp,&res2);			// let mysql do the work ;)
			    if (rows2==1) {
				row2=mysql_fetch_row(res2);
				if (row2[0][0]=='1') {
				    match=1;
				}
			    }
			    if (res2) mysql_free_result(res2),res2=NULL;
			}
			hdrs=hdrs->next;
		    }
		}
		else match=1;						// no additional matches necessary
	    }
	    if (res) mysql_free_result(res),res=NULL;
	    if (match) {						// were there any matches?
		switch (row[FILT_action][0]) {				// yup, get to work
		    case 'F':						// use different recipient address
			sprintf(tmp,"matched forward rule %s",row[FILT_id]);
			priv->hdrs=newHdr(priv,priv->hdrs,"X-flexi-rule",tmp);
			priv->hdrs=newHdr(priv,priv->hdrs,"X-flexi-newrcpt",row[FILT_forward]);
			smfi_delrcpt(ctx,rcpt->email);			// remove original recipient
			smfi_addrcpt(ctx,row[FILT_forward]);		// set new recipient TODO: allow multiple?
			rcpts++;
		    	break;;
		    case 'A':						// accept original recipient
			sprintf(tmp,"matched forward rule %s",row[FILT_id]);
			priv->hdrs=newHdr(priv,priv->hdrs,"X-flexi-rule",tmp);
			rcpts++;
		    	break;;
		    case 'D':
			smfi_delrcpt(ctx,rcpt->email);			// remove recipient
#ifdef DEBUG
			fprintf(stderr,"removing rcpt %s\n",rcpt->email);
#endif
		    	break;;
		}
	    }
	    else {							// no filters for this recipient, default action deliver
		priv->hdrs=newHdr(priv,priv->hdrs,"X-flexi-status","processed - no filter matches");
		rcpts++;
	    }
	    rcpt=rcpt->next;						// next recipient (if any)
	}
#ifdef DEBUG
	    fprintf(stderr,"Final recipients: %d\n",rcpts);
#endif
    	if (rcpts>0) {
	    rv=SMFIS_ACCEPT;
	}
	else {
	    smfi_setreply(ctx,"550","5.1.6","recipient unknown / 55");
	    rv=SMFIS_REJECT;
	}
    }
    if (priv->hdrs) {
	struct hdrs *h=priv->hdrs;
	while (h) {
	    smfi_insheader(ctx,255,h->headerf,h->headerv);
	    h=h->next;
	}
    }
    // cleanup memory after message processing is finished
    if (priv->rcpt) {
	struct rcpt *c,*r;
	c=priv->rcpt;
	while ((r=c->next)) {
	    free((void *)c);
	    c=r;
	}
	free((void *)c);
	priv->rcpt=NULL;
    }
    if (priv->hdrs) {
	struct hdrs *c,*r;
	c=priv->hdrs;
	while (c) {
	    r=c->next;
	    if (c->hdrf) free(c->hdrf);
	    if (c->hdrv) free(c->hdrv);
	    free((void *)c);
	    c=r;
	}
	priv->hdrs=NULL;
    }
    if (priv->mlhd) {
	struct hdrs *c,*r;
	c=priv->mlhd;
	while (c) {
	    r=c->next;
	    if (c->hdrf) free(c->hdrf);
	    if (c->hdrv) free(c->hdrv);
	    free((void *)c);
	    c=r;
	}
	priv->mlhd=NULL;
    }
    if (hdrf) free(hdrf),hdrf=NULL;
    if (sender) free(sender);
    if (hsender) free(hsender);
    if (subject) free(subject);
    return (rv?rv:SMFIS_ACCEPT);
}


sfsistat
tmfi_envfrom(ctx,from)
     SMFICTX *ctx;
     char **from;
{
    char *myqp;
    MYSQL_RES *res=NULL;
    int rows;
    MYSQL_ROW row;
    struct flexiPriv *priv;

    GETPRIV;
#ifdef DEBUG
fprintf(stderr,"envfrom %s\n",from[0]);
#endif
#ifdef WRITELOG
{
size_t s;
	fputc((int)'f',fh);
	fwrite(&priv,sizeof(void *),1,fh);
	s=strlen(from[0]);
	fwrite(&s,sizeof(size_t),1,fh);
	fwrite(from[0],strlen(from[0]),1,fh);	// ignore other arguments for now
	fflush(fh);
}
#endif

    // TODO: check for unconditional blacklist entries - can/do we want to block here already?
    if (priv->rv==SMFIS_ACCEPT) return(SMFIS_CONTINUE);	// whitelisted, continue until body

    priv->envfrom=scaleTmp(priv->envfrom,strlen(from[0])*4);
    // TODO: remove "<>" from mail address !?
    mysql_real_escape_string_quote(priv->my,priv->envfrom->str,from[0],strlen(from[0]),'\'');
    myqp=myquery(priv,"\
select id,action from bwlist where field='F' and \
((matchtype='P' and '%s' like pattern) or \
(matchtype='R' and '%s' rlike pattern)) order by prio,action desc limit 1",priv->envfrom->str,priv->envfrom->str);

    rows=getData(priv->my,myqp,&res);
    if (rows==1) {
	char myhv[MAXHEADERV];
	row=mysql_fetch_row(res);
	sprintf(myhv,"bwlist.id=%s/%s/%s",row[0],row[1],from[0]);
	priv->hdrs=newHdr(priv,priv->hdrs,"X-flexi-rule",myhv);
	if (row[1][0]=='B') 	// blacklisted, reject connection
	    priv->rv=SMFIS_REJECT;
	else if (row[1][0]=='W')	// accept unconditionally - really?
	    priv->rv=SMFIS_ACCEPT;
    }
    if (res) { mysql_free_result(res); res=NULL; }
#ifdef DEBUG
    fprintf(stderr,"Return: %d\n",(priv->rv!=SMFIS_REJECT)?SMFIS_CONTINUE:priv->rv);	// reject if blacklisted, otherwise continue
#endif
    return((priv->rv!=SMFIS_REJECT)?SMFIS_CONTINUE:priv->rv);	// reject if blacklisted, otherwise continue
}

sfsistat
tmfi_envto(ctx,to)
     SMFICTX *ctx;
     char **to;
{
    char *myqp;
    MYSQL_RES *res;
    int rows;
    MYSQL_ROW row;
    sfsistat rv=0;
    struct flexiPriv *priv;

#ifdef DEBUG
    fprintf(stderr,"envto %s\n",to[0]);
#endif
    GETPRIV;
#ifdef WRITELOG
    {
    size_t s;
	    fputc((int)'t',fh);
	    fwrite(&priv,sizeof(void *),1,fh);
	    s=strlen(to[0]);
	    fwrite(&s,sizeof(size_t),1,fh);
	    fwrite(to[0],strlen(to[0]),1,fh);	// ignore other arguments for now
	    fflush(fh);
    }
#endif

    // remove leading and trailing "<>" characters - rcpt ought to be in "<...>" format
    if (*to[0] == '<') to[0]++;					// should always match!
    if (to[0][strlen(to[0])-1]=='>') to[0][strlen(to[0])-1]=0;	// should also always match

    // TODO: check for unconditional blacklist entries - can/do we want to block here already?
    // with multiple recipient, is it smart to have a general white/blacklist?
    if (priv->rv==SMFIS_ACCEPT) return(SMFIS_CONTINUE);	// whitelisted, continue until body

    priv->tmp=scaleTmp(priv->tmp,strlen(to[0])*4);
    mysql_real_escape_string_quote(priv->my,priv->tmp->str,to[0],strlen(to[0]),'\'');
    myqp=myquery(priv,"\
select id,action from bwlist where field='T' and \
((matchtype='P' and '%s' like pattern) or \
(matchtype='R' and '%s' rlike pattern)) order by prio,action desc limit 1",priv->tmp->str,priv->tmp->str);

    rows=getData(priv->my,myqp,&res);
    if (rows==1) {
	char myhv[MAXHEADERV];
	row=mysql_fetch_row(res);
	sprintf(myhv,"bwlist.id=%s %s %s",row[0],row[1],to[0]);
	priv->hdrs=newHdr(priv,priv->hdrs,"X-flexi-rule",myhv);
	if (row[1][0]=='B') { 				// blacklisted, reject recipient
	    smfi_setreply(ctx,"550","5.1.6","recipient unknown / 42");
	    rv=SMFIS_REJECT;
	}
	else {
	    if (row[1][0]=='W')			// accept recipient and whitelist message
		rv=SMFIS_ACCEPT;
	}
	if (res) mysql_free_result(res),res=NULL;
    }
    else {
	// no black/whitelist rule for this recipient - check if recipient is known 
	// can't decide on the action yet, though, as not all parameters are queried
	myqp=myquery(priv,"\
select action,rcpt,prio from filter where (('%s' like concat('%%',rcpt,'%%') and rcpttype='P') or ('%s' rlike rcpt and rcpttype='R')) order by prio limit 1",
		to[0],to[0]);
	rows=getData(priv->my,myqp,&res);
	if (rows!=1) {
	    //smfi_setreply(ctx,"550","5.1.6","unknown recipient / 46");	// TODO - allow relaying for certain senders?!
	    //rv=SMFIS_REJECT;
	    rv=SMFIS_CONTINUE;
	}
	else {	// got a line - if it's a fallback-line (prio 255) and the rcpt starts with "@", do what the action says ...
	    // TODO: maybe there should rather be a fallback definition in the domain,
	    // so that when there are no matches here, the definition there counts ...
	    row=mysql_fetch_row(res);
	    if (row[1][0]=='@' && atoi(row[2])==255) {	// yup, fallback
		switch (row[0][0]) {
		    case 'A':			// Accept
		    case 'F':			// forward
			rv=SMFIS_ACCEPT;
			break;;
		    case 'D':			// get outa here!
			rv=SMFIS_REJECT;
			smfi_setreply(ctx,"550","5.1.6","recipient unknown / 52");
			break;;
		    default:
			// wtf?
			break;;
		}
	    }
	    else {
		// not a fallback line, decide later
	    }
	}
    }
    if (res) mysql_free_result(res),res=NULL;
    if (rv!=SMFIS_REJECT) 				// remember rcpt
	priv->rcpt=newRcpt(priv->rcpt,to[0]);
    return((rv!=SMFIS_REJECT)?SMFIS_CONTINUE:rv);	// reject rcpt if blacklisted, otherwise continue
}

sfsistat
tmfi_header(ctx,headerf,headerv)
     SMFICTX *ctx;
     char *headerf;
     char *headerv;
{
    /*char myq[16384],*myqp;
    MYSQL_RES *res;
    int rows;
    MYSQL_ROW row;
    sfsistat rv=0;*/
    struct flexiPriv *priv;

    GETPRIV;
#ifdef WRITELOG
{
size_t s;
	fputc((int)'h',fh);
	fwrite(&priv,sizeof(void *),1,fh);
	s=strlen(headerf);
	fwrite(&s,sizeof(size_t),1,fh);
	fwrite(headerf,strlen(headerf),1,fh);
	s=strlen(headerv);
	fwrite(&s,sizeof(size_t),1,fh);
	fwrite(headerv,strlen(headerv),1,fh);
	fflush(fh);
}
#endif
    priv->mlhd=newHdr(priv,priv->mlhd,headerf,headerv);	// should we check whether the header field is even used in the rules before storing it?
#ifdef DEBUG
fprintf(stderr,"header %s:%s\n",headerf,headerv);
#endif
    return SMFIS_CONTINUE;			// do we need to check headers now already?
}

sfsistat
tmfi_eoh(ctx)
     SMFICTX *ctx;
{
    /*char myq[16384],*myqp;
    MYSQL_RES *res;
    int rows;
    MYSQL_ROW row;
    sfsistat rv=0; */
#ifdef WRITELOG
    struct flexiPriv *priv;

    GETPRIV;
{
    fputc((int)'H',fh);
    fwrite(&priv,sizeof(void *),1,fh);
    fflush(fh);
}
#endif
    return SMFIS_CONTINUE;			// do we need to check headers now already?
}

struct smfiDesc tmfilter =
{
    "flexi",  /* filter name */
    SMFI_VERSION,   /* version code -- do not change */
    SMFIF_ADDHDRS|SMFIF_CHGHDRS|SMFIF_CHGBODY|SMFIF_ADDRCPT|SMFIF_ADDRCPT_PAR|SMFIF_DELRCPT|SMFIF_QUARANTINE|SMFIF_CHGFROM|SMFIF_SETSYMLIST,
    tmfi_connect,       /* connection info filter */
    tmfi_helo,          /* SMTP HELO command filter */
    tmfi_envfrom,       /* envelope sender filter */
    tmfi_envto,         /* envelope recipient filter */
    tmfi_header,        /* header filter */
    tmfi_eoh,	        /* end of header */
    NULL,       /* body block filter */
    tmfi_eom,  /* end of message */
    NULL,       /* message aborted - TODO: should we do special handling? */
    tmfi_close,       /* connection cleanup */
#if SMFI_VERSION > 2
    NULL,       /* unknown SMTP commands */
#endif /* SMFI_VERSION > 2 */
#if SMFI_VERSION > 3
    NULL,       /* DATA command */
#endif /* SMFI_VERSION > 3 */
#if SMFI_VERSION > 4
    NULL        /* Negotiate, at the start of each SMTP connection */
#endif /* SMFI_VERSION > 4 */
};

static void
usage(prog)
    char *prog;
{
    fprintf(stderr, "Usage: %s SOCKET_PATH\n", prog);
}

void cleanup()
{
    // if (my) mysql_close(my),my=NULL;
}

int
main(argc, argv)
     int argc;
     char **argv;
{
    char *socket;
    struct stat status;
#ifdef WRITELOG
    char fn[256];
    struct tm *tm;
    time_t t;
#endif

    if (argc > 2)
    {
        usage(argv[0]);
        exit(EX_USAGE);
    }

    if (argc<2)
	socket="/var/run/flexi.sock";
    else
	socket = argv[1];

    //openMysql();	// done in the flexiPriv setup
    // initialize MySQL library specifically now, as it's not thread-safe and
    // might otherwise cause mysql_init() to fail ... saving mutex on the 
    // mysql_init this way ...
    if (mysql_library_init(0,NULL,NULL)) {
	fprintf(stderr,"MySQL library init failed\n");
	exit(EX_SOFTWARE);
    }

    if (smfi_setconn(socket) == MI_FAILURE)
    {
        (void) fprintf(stderr, "smfi_setconn failed\n");
	doLog(LOG_CRIT,"smfi_setconn failed");
	cleanup();

	mysql_library_end();
        exit(EX_SOFTWARE);
    }

    /* attempt to remove existing socket, if possible */
    if (stat(socket, &status) == 0 && S_ISSOCK(status.st_mode))
    {
        unlink(socket);
    }

#ifdef WRITELOG
    t=time(NULL);
    tm=localtime(&t);
    sprintf(fn,"/tmp/mtm-%04d%02d%02d-%02d%02d%02d.log",tm->tm_year+1900,tm->tm_mon,tm->tm_mday,tm->tm_hour,tm->tm_min,tm->tm_sec);
    fh=fopen(fn,"wb");
#endif

    if (smfi_register(tmfilter) == MI_FAILURE)
    {
        fprintf(stderr, "smfi_register failed\n");
	doLog(LOG_CRIT,"smfi_register failed");
	mysql_library_end();
        exit(EX_UNAVAILABLE);
    }

    doLog(LOG_INFO,"process started");
    return smfi_main();
}

// vim: ai smartindent sw=4 nowrap
