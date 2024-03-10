<?php

require_once 'vendor/autoload.php';

function error(string $message)
{
	$colors = new Wujunze\Colors();
	die($colors->getColoredString($message, "black", "red"));
}

function warning(string $message)
{
	$colors = new Wujunze\Colors();
	echo $colors->getColoredString($message, "black", "yellow") . PHP_EOL;
}

if($argc < 2)
	error("Usage: composer run render <pattern> [--song-length-database=path]");

exec("ffmpeg -version", $output, $code);

if($code !== 0)
	error("Failed to run ffmpeg, please ensure it is available on your path");

$args = new Phalcon\Cop\Parser();
$params = $args->parse($argv);

$database = null;

if(!isset($params['song-length-database']))
	warning("No song length database provided. Default length will be used.");
else
{
	$filename = $params['song-length-database'];

	if(!file_exists($filename))
		error("Specified song length database file does not exist");

	$database = new Dnoegel\SidInfo\SongLengthDatabase($filename);
}

$files = glob($argv[1]);

if($files === 0)
	error("Expected one or more files, matched none");

$parser = new Dnoegel\SidInfo\SidParse();
$index = 0;

foreach($files as $filename)
{
	$progress = round(100 * (($index++) / count($files)));

	$seconds = 120;

	if(file_exists($filename . ".flac"))
	{
		echo "$progress - Skipping $filename - already rendered" . PHP_EOL;
		continue;
	}

	try{
		$struct = $parser->parseFile($filename);
		$length = $database->find($struct->getHash());

		$hours = 0;
		list($mins,$secs) = explode(':',$length[0]);
		$seconds = mktime($hours,$mins,$secs) - mktime(0,0,0);

	}catch(Exception $e) {
		warning("Failed to determine length of $filename - " . $e->getMessage());
	}

	$sid = escapeshellarg($filename);
	$wav = escapeshellarg($filename . ".wav");

	$command = "sid2wav -16 -s -t$seconds $sid $wav";

	echo "$progress% - Rendering $wav..." . PHP_EOL;

	@unlink($filename . ".wav"); // NB: Always overwrite

	$output = "";

	exec($command, $output, $code);

	if($code !== 0)
	{
		warning("Failed to render $filename ($output)");
		continue;
	}

	$flac = escapeshellarg($filename . ".flac");
	$title = escapeshellarg($struct->getTitle());
	$artist = escapeshellarg($struct->getArtist());

	$command = "ffmpeg -y -loglevel error -i $wav -af aformat=s16:44100 -metadata title=$title -metadata artist=$artist $flac";

	echo "$progress% - Encoding $flac..." . PHP_EOL;

	$output = "";

	exec($command, $output, $code);

	if($code !== 0)
	{
		error("Failed to encode $flac ($output)");
		exit;
	}

	unlink($filename . ".wav");
}