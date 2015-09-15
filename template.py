html1 = r"""
<html>
<head>
  <title>Lexical Databases with HELP of the EDICTOR</title>
  <meta http-equiv="content-type" content="text/html; charset=utf-8">
  <script src="script/jquery-1.10.2.js"></script>
  <script src="script/bootstrap.min.js"></script>
  <script src="script/bootstrap-multiselect.js"></script>
  <link rel="stylesheet" type="text/css" href="script/main.css" />
</head>
<body>
  <div style="margin:10px">
  <h1>Select your settings for the database</h1>
  <div>

    <p>Use this template to organize, which part of the database you want to 
    inspect, work on, or check. By using the selections below, you can choose which doculects should
    be displayed, which concepts the selection should contain, and which columns (the main fields of
    data) the resulting wordlist should contain. The result is a specific URL that communicates all
    necessary parameters to the database application and can also be bookmarked for convenience.</p>

    <label>Select Doculects:
      <select id="doculects" class="selex" multiple="" style="display: none;">
	{DOCULECTS}
      </select>
    </label>
    <br>
    <label>Select Concepts:
      <select id="concepts" class="selex" multiple="" style="display: none;">"""
html2 = """
    </select></label>
    <br>
    <label>Select Columns: 
  <select id="columns" class="selex" multiple="" style="display: none;">
    {COLUMNS}  
</select></label>
  </div>
  <button onclick="makeURL('{DBASE}');">SUBMIT</button>
  
  <div id="output"></div></div>
<script>{SCRIPT}</script>
</body>
</html>"""
script = """
$('#concepts').multiselect({
      disableIfEmtpy: true,
      includeSelectAllOption : true,
      enableFiltering: true,
      enableCaseInsensitiveFiltering: true,
 }
    );

$('#doculects').multiselect({
      disableIfEmtpy: true,
      includeSelectAllOption : true,
      enableFiltering: true,
      enableCaseInsensitiveFiltering: true,
}
    );

$('#columns').multiselect({
      disableIfEmtpy: true,
      includeSelectAllOption : true,
      enableFiltering: true,
      enableCaseInsensitiveFiltering: true,
}
    );

var sortprefs = {
  'DOCULECT' : 1,
  'CONCEPT' : 3,
  'GLOSS_IN_SOURCE' :2,
  'IPA' : 5,
  'ENTRY_IN_SOURCE' : 4,
  'TOKENS' : 6,
  'ALIGNMENT' : 7,
  'NOTE' : 8
};

function makeURL(dbase) {
  
  url = 'http://tsv.lingpy.org?remote_dbase='+dbase
    + '&file='+dbase+'&preview=280'
    + '&basics=DOCULECT|CONCEPT|IPA|TOKENS|POS|NOTE|COGID|SOURCE'
    ;

  /* get selected doculects */
  var docs = document.getElementById('doculects');
  var doculects = [];
  for (var i=0,doc; doc=docs.options[i]; i++) {
    if (doc.selected) {doculects.push(doc.value);
    }
  }

  if (doculects.length != docs.options.length && doculects.length > 0) {
    url += '&doculects='+doculects.join('|');
  }


  /* get selected doculects */
  var docs = document.getElementById('concepts');
  var concepts = [];
  for (var i=0,doc; doc=docs.options[i]; i++) {
    if (doc.selected) {concepts.push(doc.value);
    }
  }
  if (concepts.length != docs.options.length && concepts.length > 0) {
    url += '&concepts='+concepts.join('|');
  }

  /* get selected doculects */
  var docs = document.getElementById('columns');
  var columns = [];
  for (var i=0,doc; doc=docs.options[i]; i++) {
    if (doc.selected) {columns.push(doc.value);
    }
  }
  columns.sort(
      function (x,y) {
	_x = (x in sortprefs) ? sortprefs[x] : x.charCodeAt(0);
	_y = (y in sortprefs) ? sortprefs[y] : y.charCodeAt(0);
	return _x - _y;
      });
  console.log(columns);
  
  if (columns.length != docs.options.length && columns.length > 0) {
    url += '&columns='+columns.join('|');
  }

  /* output the url */
  var output = document.getElementById('output');
  output.innerHTML = '<br><br><p style="margin-top:30px;width:60%;font-size:16px">Press <a style="color:red;font-weight:bold;" href="'+url+'" target="_blank">here</a> to open the database '
    + 'with your specified settings, or paste the link below in your browser.</p>';
  output.innerHTML += '<pre style="width:60%"><code>'+url+'</code></pre>';

}

function MakeTable() {

  var cobu = document.getElementById('coverage_button');
  var json_table = document.getElementById('coverage');

  if (cobu.innerHTML == 'Show Current Data Status') {
    cobu.innerHTML = 'Hide Current Data Status';
    var header = ['iso','subgroup','entries','source','url'];
    var nheader = ['ISO','SUBGROUP','ENTRIES','SOURCE','URL'];
    var out = '';
    out += '<table style="cellspacing:2px;border:2px solid black">';
    out += '<tr><th style="padding:4px;border:1px solid black">NAME</th><th style="padding:4px;border:1px solid black;">'+nheader.join('</th><th style="padding:4px;border:1px solid black;">')+'</th></tr>';
    keys = Object.keys(META);
    keys.sort();
    for(var j=0,key; key=keys[j]; j++) {
      out += '<tr>';
      out += '<td style="border:1px solid black;padding:4px;">'+key+'</td>';
      for (var i=0,h; h=header[i]; i++) {
        if (h == 'iso') {
          var val = '<a href="http://ethologue.com/language/'+META[key][h]+'">'+META[key][h]+'</a>';
        }
        else if (h == 'source') {
          var val = '<a href="http://bibliography.lingpy.org?key='+META[key][h]+'">'+META[key][h]+'</a>';
        }
        else if (h == 'url') {
          var val = '<a href="'+META[key][h]+'">'+META[key][h]+'</a>';
        }
        else {
          var val = META[key][h];
        }
        out += '<td style="padding:4px;border:1px solid black;">'+val+'</td>';
      }
      out += '</tr>';
    }
    out += '</table>';
    json_table.innerHTML = out;
  }
  else {
    cobu.innerHTML = 'Show Current Data Status';
    json_table.innerHTML = '';
  }
}
"""
