#/bin/bash

#Catam cleanup
#clean up import dir


cd /local/data/temp/htdocs-maths/undergrad/catam

echo "<body><h1>DELETE ME</h1></body>" > import-index

find . -type d -exec ln import-index "{}/index.html" \;

rm import-index

find . -name "*~" -exec rm "{}" \;

find . -name "*html-*" -exec rm "{}" \;

rm -rf calendar/ caltest/

rm -rf ccatsl/manual*

rm docs

rm -rf ./software/matlabinstall/RCS-backup

rm data/index-backup.html
