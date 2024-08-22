# Backups directory
This directory should be made inaccessible via web, but should still be accessible via PHP

```
cat <<EOF > /etc/cron.d/traqr
SHELL=/bin/bash
PATH=/sbin:/bin:/usr/sbin:/usr/bin:/eri/sbin
MAILTO=root
HOME=/

47 0 * * * root wget http://traqr.eri.ucsb.edu/Util/dbBackup-CronDaily.php -o /dev/null -O /dev/null
EOF
```
