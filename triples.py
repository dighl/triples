#!/usr/bin/python2
import cgi
import sqlite3

print "Content-type: text/plain; charset=utf-8"
print

# get the args of the url, convert nasty field storage to plain dictionary,
# there is probably a better solution, but this works for the moment
tmp_args = cgi.FieldStorage()
args = {}
for arg in tmp_args:
    args[arg] = tmp_args[arg].value

# check for dbase arg and switch the database in case an argument is provided
if 'remote_dbase' in args:
    dbpath = args['remote_dbase']+'.sqlite3' if not \
            args['remote_dbase'].endswith('sqlite3') else \
            args['remote_dbase']
else:
    dbpath = 'triples.sqlite3'

# connect to the sqlite database
db = sqlite3.connect(dbpath)
cursor = db.cursor()

# load the table if this is specified
if 'file' in args:

    # get unique columns
    if not 'columns' in args:
        cols = [line[0] for line in cursor.execute(
            'select distinct COL from '+args['file']+';')]
    else:
        cols = args['columns'].split('|')

    # get unique concept strings
    #concepts = [line[0] for line in cursor.execute(
    #    'select distinct CONCEPT from '+args['file']+';')]
    #doculects = [line[0] for line in cursor.execute(
    #    'select distinct DOCULECT from '+args['file']+';')]

    print 'ID\t'+'\t'.join(cols)
    
    # if neither concepts or doculects are passed from the args, all ids are
    # selected from the database
    if not 'concepts' in args and not 'doculects' in args:
        idxs = [line[0] for line in cursor.execute(
            'select distinct ID from '+args['file']+';')]
    else:
        # we evaluate the concept string
        cstring = 'COL = "CONCEPT" and VAL in ("'+'","'.join(args['concepts'].split('|'))+'")' if \
                'concepts' in args else ''
        dstring = 'COL = "DOCULECT" and VAL in ("'+'","'.join(args['doculects'].split('|'))+'")' if \
                'doculects' in args else ''
        
        cidxs = [line[0] for line in cursor.execute(
            'select distinct ID from '+args['file'] + ' where '+cstring)] if \
                    cstring else []
        didxs = [line[0] for line in cursor.execute(
            'select distinct ID from '+args['file'] + ' where '+dstring)] if \
                    dstring else []

        if cidxs and didxs:
            idxs = [idx for idx in cidxs if idx in didxs]
        else:
            idxs = cidxs or didxs

    # make the dictionary
    D = {}
    for a,b,c in cursor.execute('select * from '+args['file']+';'):
        if c not in ['-','']:
            try:
                D[a][b] = c.encode('utf-8')
            except KeyError:
                D[a] = {b:c.encode('utf-8')}
    # make object
    for idx in idxs:
        print str(idx),
        for col in cols:
            try:
                print '\t'+D[idx][col],
            except:
                print '\t',
        print '\n',
        
if 'remote' in args:

    with open(args['remote']) as f:
        print(f.read())
