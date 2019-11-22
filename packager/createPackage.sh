# createPackage.sh
# creates a package for dbDIPview
# The package can contain:
#   - optional: CVS database content and structure
#   - queries
# The name of the package must be the name of the schema.
# Ouput:
#    - zip package if it contains only queries
#    - tar.gz package if it contains csv data (this was native mode)

# Boris Domajnko

export LANG=sl_SI.UTF-8

if [ $# -eq 0 ]  # 
then
  echo "Usage: $0 <source_dir> <target_dir> <package_name>"
  echo "Example: $0 /home/dbdipview/records/SIP/GZS  /home/dbdipview/records/DIP0   GZSP"
  echo "         $0 ~/dbdipview/records/SIP/GZS  ~/dbdipview/records/DIP0   GZSP"
  exit -2
fi  

SOURCE=$1
OUTDIR=$2
OUTFILE_TAR=$OUTDIR/${3}.tar
OUTFILE_ZIP=$OUTDIR/${3}.zip

if [ ! -d $SOURCE ] 
then
  echo "ERROR: Source directory $SOURCE does not exist." 
  exit -1
fi  

if [ ! -d $OUTDIR ] 
then
  echo "ERROR: Target directory $OUTDIR does not exist." 
  exit -1
fi  

if [ -f $OUTFILE_TAR ] 
then
  echo "Target package file $OUTFILE_TAR exists." 
  rm -i $OUTFILE_TAR 
  [ -f $OUTFILE_TAR ] && exit -1
fi  

if [ -f $OUTFILE_ZIP ] 
then
  echo "Target package file $OUTFILE_ZIP exists." 
  rm -i $OUTFILE_ZIP 
  [ -f $OUTFILE_ZIP ] && exit -1
fi  

echo "Validating xml..."
php validator.php $SOURCE
echo

if [ -d data ] ; then
    echo Error: missing data/
    sleep 1
    exit 1
fi

ALLMETADATA="metadata/queries.xml metadata/list.txt metadata/info.txt"

ls ${SOURCE}/data/* > /dev/null 2> /dev/null
if [ $? != 0 ] ; then
    echo 'No files in data/'
    echo "Creating package ${OUTFILE_ZIP}..."
	cd  ${SOURCE} && \
	zip -r ${OUTFILE_ZIP} $ALLMETADATA && \
	echo Done. Result directory ${OUTDIR}: && \
	ls -l ${OUTDIR}
else
	ALLDATA='data/*'
	ALLMETADATA="$ALLMETADATA metadata/createdb.sql"
	if [ -f ${SOURCE}/metadata/createdb01.sql ] ; then
		ALLMETADATA="$ALLMETADATA metadata/createdb01.sql"
	fi
	if [ -f ${SOURCE}/metadata/redactdb.sql ] ; then
		ALLMETADATA="$ALLMETADATA metadata/redactdb.sql"
	fi
	if [ -f ${SOURCE}/metadata/redactdb01.sql ] ; then
		ALLMETADATA="$ALLMETADATA metadata/redactdb01.sql"
	fi
    echo "Creating package ${OUTFILE_TAR} with $ALLMETADATA..."
	cd  ${SOURCE} && \
	tar cf ${OUTFILE_TAR} $ALLMETADATA $ALLDATA && \
	gzip ${OUTFILE_TAR} && \
	echo Done. Result directory ${OUTDIR}: && \
	ls -l ${OUTDIR}
fi



