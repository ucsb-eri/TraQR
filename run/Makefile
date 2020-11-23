all:

bk: backup

backup:
	cp ../var/dbs/traqr.sqlite3 ../var/dbs/traqr.sqlite3-$$(date +'%Y%m%d-%H%M%S')

perms:
	sudo chown -R apache:apache ../var

# this is to allow a git pull into var by ordinary user
# if pull is blown, reset with: git reset --hard ORIG_HEAD
unperms:
	sudo chown -R aaron:aaron ../var


# current setup takes two executables, I think I would like to have a script that
# provides an argument for the version file and is fed a list of files to check...
version:
	(cd ..; bin/version-gen)
