all:

bk: backup

backup:
	cp ../var/dbs/covidqr.sqlite3 ../var/dbs/covidqr.sqlite3-$$(date +'%Y%m%d-%H%M%S')

perms:
	sudo chown -R apache:apache ../var
