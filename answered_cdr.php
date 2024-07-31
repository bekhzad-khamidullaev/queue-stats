<?php
require_once "config.php";
include "sesvars.php";
//ini_set('display_errors',1);
//error_reporting(E_WARNING);

//query mixed from queuelog and cdr (queuelog table must be in cdr databases)
$sql = "select queuelog.time, queuelog.callid, queuelog.queuename, queuelog.agent,  queuelog.event, queuelog.data1 as wait, queuelog.data2 as dur, cdr.did, cdr.src, cdr.recordingfile, cdr.disposition from queuelog, cdr where queuelog.time >= '$start' AND queuelog.time <= '$end' AND queuelog.callid = cdr.uniqueid and queuelog.event in ('COMPLETECALLER', 'COMPLETEAGENT') and queuelog.agent in ($agent) and queuelog.queuename in ($queue) and cdr.disposition = 'ANSWERED' order by queuelog.time";

$res = $connection->query($sql);

$out = array();
$rec = array();




while ($row = $res->fetch_assoc()) {
	$row['rec'] = getRec($row['recordingfile'], $row['calldate']);
	$out[] = $row;
}

$header_pdf = array("Дата", "CallerId", "DID", "Очередь", "Агент", "Ожид.", "Разг.");
$width_pdf = array(40, 32, 25, 25, 64, 25, 25);
$title_pdf = "Принятые вызовы";
$data_pdf = array();
foreach ($out as $k => $r) {
    $time = strtotime($r['time']);
    $time = date('Y-m-d H:i:s', $time);
    $dur = seconds2minutes($r['dur']);
    $wait = seconds2minutes($r['wait']);
    $linea_pdf = array($time, $r['src'], $r['did'], $r['queuename'], $r['agent'], $wait, $dur);
    $data_pdf[] = $linea_pdf;
}

$out = json_encode($out);

$connection->close();

function getRec($recfile, $time) {
    $time = strtotime($time);
    if (file_exists($recfile) && preg_match('/(.*)\..+$/i', $recfile)) {
        $tmpRes = base64_encode($recfile);
    } else {
        $tmpRes = isset($_REQUEST['recfile']) ? $_REQUEST['recfile'] : 'null';
    }
    return $tmpRes;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Asterisk Call Center Stats</title>
<style type="text/css" media="screen">@import "css/basic.css";</style>
<style type="text/css" media="screen">@import "css/tab.css";</style>
<style type="text/css" media="screen">@import "css/table.css";</style>
<style type="text/css" media="screen">@import "css/fixed-all.css";</style>
<link href="css/jquery.dataTables.css" rel="stylesheet">
<script src="js/1.10.2/jquery.min.js"></script>
<script src="js/handlebars.js"></script>
<script src="js/jquery.dataTables.cdr.js"></script>
<script src="js/locale.js"></script>
<script>
let outs = <?php echo $out; ?>;

function outOverData(arr) {
    let eve = {};
    let res = {};
    arr.map(v => [v.agent, v.event]).map(v => eve[v] = (eve[v] || 0) + 1);
    arr.map(v => [v.agent, v.disposition]).map(v => eve[v] = (eve[v] || 0) + 1);
    Object.keys(eve).map(v => v.split(",")).map((v, i) => {
        return [v[0], v[1], Object.values(eve)[i]];
    }).map(v => {
        if (v[0] in res) {
            let agent = res[v[0]];
            let event = { [v[1]]: v[2] };
            res[v[0]] = { ...agent, ...event };
        } else {
            let agent = { "agent": v[0] };
            let event = { [v[1]]: v[2] || 0 };
            res[v[0]] = { ...agent, ...event };
        }
    });
    return res;
}

var over_out = outOverData(outs);
over_out = JSON.stringify(over_out);
over_out = over_out.replace(/NO\sANSWER/g, "NO_ANSWER");
over_out = JSON.parse(over_out);

$(function() {
    var theTemplateScript = $("#overs-template").html();
    var theTemplate = Handlebars.compile(theTemplateScript);
    var context = { over: over_out };
    var theCompiledHtml = theTemplate(context);
    $(".overs-placeholder").html(theCompiledHtml);
});

$(function() {
    var theTemplateScript = $("#out-template").html();
    var theTemplate = Handlebars.compile(theTemplateScript);
    var context = { out: outs };
    var theCompiledHtml = theTemplate(context);
    $('.out-placeholder').html(theCompiledHtml);
});

$(document).ready(function() {
    if (navigator.language == 'ru')
        $('#incTable').DataTable({
            "language": dataTablesLocale['ru'],
            "iDisplayLength": 100
        });
    else
        $('#incTable').DataTable({ "iDisplayLength": 100 });
});

Handlebars.registerHelper("prettyDate", function (timestamp) {
    var a = new Date(timestamp * 1000);
    var months = navigator.language == 'ru' ? 
        ['Янв','Фев','Мар','Апр','Май','Июня','Июля','Авг','Сен','Окт','Ноя','Дек'] : 
        ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var year = a.getFullYear();
    var month = months[a.getMonth()];
    var date = a.getDate();
    var hour = (a.getHours() < 10 ? '0' : '') + a.getHours();
    var min = (a.getMinutes() < 10 ? '0' : '') + a.getMinutes();
    var sec = (a.getSeconds() < 10 ? '0' : '') + a.getSeconds();
    var time = a < 3600000 ? min + ':' + sec : date + ' ' + month + ' ' + hour + ':' + min + ':' + sec;
    return time;
});

Handlebars.registerHelper("dataNorm", function (d) {
    return d == undefined ? "0" : d;
});

Handlebars.registerHelper("html5Player", function (p) {
    return '<audio id="player" controls preload="none"><source src="dl.php?f=' + p + '"></audio>';
});

Handlebars.registerHelper("getStatus", function (s) {
    let status = s === 'COMPLETECALLER' ? '<span style="color: limegreen">Абонент</span>' : 
                 s === 'COMPLETEAGENT' ? '<span style="color: royalblue">Агент</span>' : '';
    return status;
});
</script>
<script id="overs-template" type="text/x-handlebars-template">
<h2>Обзор</h2>
<div class="table">
    <table class="table centered">
        <thead>
            <tr class="text-center">
                <th class="text-left">Агент</th>
                <th>Отв.</th>
            </tr>
        </thead>
        <tbody>
            {{#each over}}
            <tr class="text-center">
                <td class="text-left">{{@key}}</td>
                <td>{{dataNorm this.ANSWERED}}</td>
            </tr>
            {{/each}}
        </tbody>
    </table>
</div>
</script>
<script id="out-template" type="text/x-handlebars-template">
<div class="table table-list-search">
    <table id="incTable" class="table table-striped">
        <thead>
            <tr>
                <th>Дата</th>
                <th>CalerID</th>
                <th>DID</th>
                <th>Очередь</th>
                <th>Агент</th>
                <th>Ожид.</th>
                <th>Разг.</th>
                <th>Заверш.</th>
                <th>Запись</th>
            </tr>
        </thead>
        <tbody>
            {{#each out}}
            <tr>
                <td>{{prettyDate callid}}</td>
                <td>{{src}}</td>
                <td>{{did}}</td>
                <td>{{queuename}}</td>
                <td>{{agent}}</td>
                <td>{{prettyDate wait}}</td>
                <td>{{prettyDate dur}}</td>
                <td>{{{getStatus event}}}</td>
                <td>{{{html5Player rec}}}</td>
            </tr>
            {{/each}}
        </tbody>
    </table>
</div>
</script>
</head>
<body>
<?php include "menu.php";?>
<div id="main">
    <div id="contents">
        <h1>Принятые вызовы: <?php echo $start . " - " . $end ?></h1>
        <br/>
        <div class="overs-placeholder"></div>
        <br/>
        <h2>Детализация</h2>
        <br/>
        <?php print_exports($header_pdf, $data_pdf, $width_pdf, $title_pdf, $cover_pdf); ?>
        <br/>
        <hr/>
        <div class="out-placeholder"></div>
    </div>
</div>
<div id='footer'><a href='https://elastix.uz'>ELASTIX.UZ</a> 2024</div>
</body>
</html>
