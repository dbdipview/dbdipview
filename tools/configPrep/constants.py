# internal states for parsing the xlsx input
WAIT_ID                 = 0
WAIT_SELECTDESC         = WAIT_ID + 1                  #1
WAIT_TITLE              = WAIT_SELECTDESC + 1          #2
WAIT_SUBTITLE           = WAIT_TITLE + 1               #3
WAIT_SCHEMA             = WAIT_SUBTITLE + 1            #4
WAIT_TABLE_MAIN         = WAIT_SCHEMA + 1              #5
WAIT_CSV_FILE           = WAIT_TABLE_MAIN + 1          #6
WAIT_HEADER             = WAIT_CSV_FILE + 1            #7
WAIT_NEXT_FIELD         = WAIT_HEADER + 1              #8

WAIT_TITLE_SUBSELECT    = WAIT_NEXT_FIELD + 1          #9
WAIT_SUBTITLE_SUBSELECT = WAIT_TITLE_SUBSELECT + 1     #10
WAIT_SCHEMA_SUBSELECT   = WAIT_SUBTITLE_SUBSELECT + 1  #11
WAIT_TABLE_SUBSELECT    = WAIT_SCHEMA_SUBSELECT + 1    #12
WAIT_CSV_FILE_SUBSELECT = WAIT_TABLE_SUBSELECT + 1     #13
WAIT_HEADER_SUBSELECT   = WAIT_CSV_FILE_SUBSELECT + 1  #14
WAIT_NEXT_FIELD_SUBS    = WAIT_HEADER_SUBSELECT + 1    #15
SKIP_ALL                = WAIT_NEXT_FIELD_SUBS + 1     #16
