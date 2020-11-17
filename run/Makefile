all:

bk: backup

backup:
	cp ../var/dbs/traqr.sqlite3 ../var/dbs/traqr.sqlite3-$$(date +'%Y%m%d-%H%M%S')

perms:
	sudo chown -R apache:apache ../var

version:
	(cd ..; bin/version-gen)
