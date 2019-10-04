<?php
function runCmd($cmd) {
	$res = array(
		0 => array("pipe", "r"),
		1 => array("pipe", "w"),
		2 => array("pipe", "r")
	);
	$process = proc_open($cmd, $res, $pipes);
	$result = false;
	if(is_resource($process)) {
		while(!feof($pipes[1])) {
			$result .= fread($pipes[0], 65535);
			$result .= fread($pipes[1], 65535);
			$result .= fread($pipes[2], 65535);
		}
		fclose($pipes[1]);
		fclose($pipes[0]);
		fclose($pipes[2]);
		$return_value = proc_close($process);
	}
	return $result;
}
function println($data) {
	echo date("[Y-m-d H:i:s] ") . "{$data}\n";
}
if(!file_exists("./data/") || !file_exists("./images/")) {
	@mkdir("./data/");
	@mkdir("./images/");
}
println("QQ FLASHSHOT DUMP TOOL BY Akkariin");
$dev_list = runCmd("adb devices");
$dev_list_exp = explode("\n", $dev_list);
if(isset($dev_list_exp[1]) && !empty($dev_list_exp[1])) {
	println("已找到设备：" . explode("	", $dev_list_exp[1])[0]);
	println("接下来本工具将会持续扫描 QQ 图片缓存目录下的文件，并会自动将闪照提取出来。");
	println("由于新版手机 QQ 增加了限制，当 QQ 后台运行或者闪照没加载的情况下文件会保持加密状态，因此本工具会提供 5 秒钟时间让您点开闪照，导出过程中请保持闪照界面打开（但是别按住屏幕查看）。");
	$init_list = Array();
	while(true) {
		$find_list = explode("\n", runCmd('adb shell ls /storage/emulated/0/tencent/MobileQQ/diskcache/ ^| grep "_fp"'));
		foreach($find_list as $image) {
			$image = trim(str_replace("\r", "", $image));
			if(!file_exists("./data/{$image}")) {
				println("扫描到新文件 {$image}，请在 5 秒内点开对应闪照，工具会自动保存它。");
				sleep(5);
				runCmd("cd data/ && adb pull \"/storage/emulated/0/tencent/MobileQQ/diskcache/{$image}\"");
				$fi = new finfo(FILEINFO_MIME_TYPE); 
				$mime_type = $fi->file("data/{$image}");
				switch($mime_type) {
					case "image/jpeg":
						copy("data/{$image}", "images/{$image}.jpg");
						break;
					case "image/png":
						copy("data/{$image}", "images/{$image}.png");
						break;
					case "image/gif":
						copy("data/{$image}", "images/{$image}.gif");
						break;
					case "image/bmp":
						copy("data/{$image}", "images/{$image}.bmp");
						break;
				}
				println("已将闪照文件 {$image} 保存到 data 目录，重命名后的文件复制到了 images 目录。");
			}
		}
		sleep(1);
	}
} else {
	println("错误：请先将手机用数据线连接到电脑并在开发者选项中启用 USB 调试功能。");
}
