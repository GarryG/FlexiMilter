flexi:	flexi.o
	gcc -g -L../sendmail-8.15.2/obj.Linux.4.4.0-34-generic.x86_64/libsm -L../sendmail-8.15.2/obj.Linux.4.4.0-34-generic.x86_64/libmilter -o flexi flexi.o -m64 -pthread  -lmilter -lsm `mysql_config --libs` 

flexi.o:	flexi.c
	gcc -Wall -m64 -g -I../sendmail-8.15.2/include `mysql_config --cflags` -c flexi.c -o flexi.o
