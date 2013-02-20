<?php
// update sync file (xml+changelog)

// 1. changelog
$date = $content = '';
$lines = array();
exec('git changelog master', $lines);
foreach ($lines as $line)
{
	if (!preg_match('/\* ([0-9-]+) ([0-9:]+) \+0800 [0-9a-f]+ (\w+?): (.*)$/', $line, $match))
		continue;
	$release = !strncmp($match[4], 'Release ', 8) ? substr($match[4], 8) : false;
	if ($release || $content === '')
	{

		$content .= "\r\n" . $match[1] . ": " . ($release ? $release : ' unreleased');
		$content .= "\r\n----------\r\n";
	}
	if (!$release)
	{
		$time = substr($match[1], 5, 2) . '/' . substr($match[1], 8, 2) . ' ' . substr($match[2], 0, 5);
		$content .= "* $time " . $match[4] . " (" . $match[3] . ")\r\n";
	}
}
$data = <<<EOF
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>ChangeLog</title>
<style type="text/css">
body { 
	background: url(bg2.png) no-repeat fixed #361212;
	color: #ffffff; width: 520px;
}
pre { 
	font-family: "微软雅黑", Tahoma; font-size: 12px;
	margin: 0; padding: 6px 5px 0 20px; line-height: 140%;
	word-wrap: break-word; overflow: hidden; 
}
</style>
</head>
<body>
<pre>
《剑网3》海鳗插件：修改日志
===================
$content
</pre>
</body>
</html>
EOF;
file_put_contents("changelog.html", $data);

// 2. sync.xml
$modules = array(
	'src/HM_Area.lua' => '纯阳气场/唐门机关范围显示、归属显示、消失倒计时',
	'src/HM_Guding.lua' => '醒目显示团队蛊鼎位置、条件触发自动吃鼎',
	'src/HM_Marker.lua' => '团队标记快速选择、集火',
	'src/HM_RedName.lua' => '小地图红名标记、身边玩家统计及共享',
	'src/HM_Target.lua' => '目标增强、BUFF放大、方向指示及连接线',
	'src/HM_TargetFace.lua' => '目标和焦点的脚底面向及圈圈指示',
	'src/HM_TargetMon.lua' => '目标技能CD监控、超大显示特殊BUFF',
	'src/HM_TargetList.lua' => '多焦点目标、目标列表、定制筛选排序',
	'src/HM_Battle.lua' => '战场竞技场助手、死后交阵眼、九宫计时、名剑币估算',
	'src/HM_Camp.lua' => '攻防屏蔽及智能选BOSS、任务BOSS计时等',
	'src/HM_Team.lua' => '团队列表保存与还原、团队快速标记',
	'src/HM_Force.lua' => '天策技能栏、纯阳气场/剑舞、一键小轻功',
	'src/HM_Ent.lua' => '娱乐聊天、个人数据发布',
	'src/HM_Jabber.lua' => '玩家斩杀喊话及统计、技能喊话、自动喊话',
	'src/HM_Locker.lua' => '目标锁定和选择、虎跑时锁定目标、只TAB玩家',
	'src/HM_PVPSound2.lua' => 'PVP斩杀音效、文字特效',
	'src/HM_Roll.lua' => '团队 ROLL 点娱乐、自动记录胜负',
	'src/HM_Suit2.lua' => '可共享部位的快速脱换装助手、橙武必备',
	'src/HM_ToolBox.lua' => '自动修装备、卖灰色、自动采集和确认、一键交易行',
	'lab/HM_Taoguan.lua' => '新年活动智能砸年兽陶罐',
);

$version = trim(file_get_contents('VERSION'));
$dword_ver = '0x0';
if (preg_match('/^(\d+)\.(\d+)\.(\d+)(?:b(\d+))?$/', $version, $match))
{
	$dword_ver = intval($match[1]) << 24;
	$dword_ver += intval($match[2]) << 16;
	$dword_ver += intval($match[3]) << 8;
	if (isset($match[4]))
		$dword_ver += intval($match[4]);
	$dword_ver = sprintf('0x%x', $dword_ver);
}

$info = $resource = '';
$lines = array();
exec('git ls-tree -l -r master', $lines);
foreach ($lines as $line)
{
	$tmp = preg_split('/\s+/', $line);
	$name = $tmp[4];
	if (!strncmp($name, '.', 1) || !strncmp($name, 'dev/', 4) || $name === 'Makefile')
		continue;
	$size = $tmp[3];
	if (isset($modules[$name]))
	{
		$info .= "    <file size=\"$size\" title=\"" . $modules[$name] . "\">$name</file>\n";
	}
	else
	{
		$resource .= "    <file size=\"$size\">$name</file>\n";
	}
}
$info = trim($info);
$resource = trim($resource);

$name = '海鳗、插件集';
$desc = '大量非常方便的 PVP 插件！ --== 海缦缦@步莲台 ==--';

$content = <<<EOF
<?xml version="1.0" encoding="utf-8" ?>
<jx3:hm xmlns:jx3="http://haimanchajian.com">
  <name><![CDATA[{$name}]]></name>
  <desc><![CDATA[{$desc}]]></desc>
  <version dword="$dword_ver">$version</version>
  <clientVersion dword="0x10000">1.0beta</clientVersion>
  <urlPrefix>https://raw.github.com/haimanman/jx3/master/</urlPrefix>
  <info>
    $info
  </info>
  <resource>
    $resource
  </resource>
</jx3:hm>
EOF;
file_put_contents('sync.xml', $content);