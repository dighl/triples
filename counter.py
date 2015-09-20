#!/usr/bin/python2.6
import cgitb
cgitb.enable()
import cgi

print "Content-type: text/html; charset=utf-8"
print ""

f = open('count.txt')
counter = int(f.read())
f.close()
counter += 1
f = open('count.txt','w')
f.write(str(counter))
f.close()
print '<body style="background-color:#FBFAF7"><p style="font-size:12px; font-weight:normal;">'+str(counter)+'.</p></body>'


