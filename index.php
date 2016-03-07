<?php 
session_start(); 
$time_start = microtime(true);
if (empty($theme)) {
	$theme = 'dark';
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Fenrirthviti's DDR Machine Stats</title>
<link rel="stylesheet" href="/js/jquery-ui-1.11.4/jquery-ui.css" type="text/css">
<link rel="stylesheet" href="/css/shared.css" type="text/css">
<?php if ($theme === 'light'): ?>
<link rel="stylesheet" href="/css/blue.css" type="text/css">
<?php else: ?>
<link rel="stylesheet" href="/css/orange.css" type="text/css">
<?php endif; ?>
<script type="text/javascript" src="js/jquery-1.12.0.min.js"></script>
<script type="text/javascript" src="js/helper-functions.js"></script>
<script type="text/javascript" src="js/filter-menus.js"></script>
<script type="text/javascript" src="js/jquery-ui.js"></script>
<script type="text/javascript">
$(function() {
    $( document ).tooltip();
  });
</script>
  <style>
  label {
    display: inline-block;
    width: 5em;
  }
  </style>
</head>
<body>
<?php
error_reporting(E_ALL); ini_set('display_errors', 1);
if (file_exists('stats.xml')) { 
	$xml = simplexml_load_file('stats.xml'); 

	#start machine total stats
	#echo '<div id="wrapper"><table><tbody><tr>
	echo '<div id="wrapper">'; #REMOVE LINE WHEN MACHINE STATS GOES BACK IN
	#<th>Last Session Date</th>
	#<th>Total Sessions</th>
	#<th>Total Notes Hit</th>
	#<th>Total Songs Played (Passed)</th></tr>';
	#$machine = $xml->GeneralData;
	#echo '<tr>';
	#echo '<td class="center">' . $machine->LastPlayedDate . '</td>';
	#echo '<td class="center">' . $machine->TotalSessions . '</td>';
	#echo '<td class="center">' . $machine->TotalTapsAndHolds . '</td>';
	#echo '<td class="center">' . $machine->NumTotalSongsPlayed . ' (' . $machine->NumStagesPassedByPlayMode->Regular . ')</td>';
	#echo '</tr></tbody></table>';

	#profile list
	$profiles = array(
		'4c860f3932e17f1f' => 'Joel',
		'7d8c471b4187fea2' => 'Alyssa',
		'9232f9ff7f1af5a4' => 'Mike'
	);
	$_SESSION['profiles'] = array_flip($profiles);
	
	#grade list
	$grades = array(
		'Tier01' => "★★★★", 'Tier02' => "★★★", 'Tier03' => "★★", 'Tier04' => "★", 
		'Tier05' => 'S+', 'Tier06' => 'S', 'Tier07' => 'S-', 'Tier08' => 'A+', 'Tier09' => 'A',
		'Tier10' => 'A-', 'Tier11' => 'B+', 'Tier12' => 'B', 'Tier13' => 'B-', 'Tier14' => 'C+',
		'Tier15' => 'C', 'Tier16' => 'C-', 'Tier17' => 'D'
	);
	
	#filter songs
	$songs = array();
	$filterattr = filter_input(INPUT_GET, 'filterattr');
	$filterval = filter_input(INPUT_GET, 'filterval');
	$packs = array();
	$difficulties = array();
	foreach ($xml->SongScores->Song as $song) {
		if (!empty ($song->Steps->HighScoreList->HighScore)) {
			foreach ($song->Steps->HighScoreList->HighScore as $HighScore) {
				if (!empty($HighScore->PlayerGuid)) {
					
					#format data array
					$fsong = new stdClass();
					list($dir, $pack, $title) = explode('/', $song->attributes()->Dir);
					$fsong->dir = $dir;
					$fsong->pack = $pack;
					$fsong->title = $title;
					$fsong->difficulty = (string) $song->Steps->attributes()->Difficulty;
					$fsong->plays = (int) $song->Steps->HighScoreList->NumTimesPlayed;
					$fsong->date = new DateTime($HighScore->DateTime);
					$fsong->player = $profiles[(string) $HighScore->PlayerGuid];
					$fsong->score = (int) $HighScore->Score;
					$fsong->grade = new stdClass();
					$fsong->grade->tier = (string) $HighScore->Grade;
					$fsong->grade->letter = $grades[(string) $HighScore->Grade];
					$fsong->grade->percentage = number_format(round((float) $HighScore->PercentDP * 100, 2), 2);
					$fsong->f = (int) $HighScore->TapNoteScores->W1;
					$fsong->e = (int) $HighScore->TapNoteScores->W2;
					$fsong->g = (int) $HighScore->TapNoteScores->W3;
					$fsong->d = (int) $HighScore->TapNoteScores->W4;
					$fsong->wo = (int) $HighScore->TapNoteScores->W5;
					$fsong->m = (int) $HighScore->TapNoteScores->Miss;

					#build filter lists
					if (!in_array($pack, $packs)) {
						$packs[] = $pack;
					}
					if (!in_array($song->Steps->attributes()->Difficulty, $difficulties)) {
						$difficulties[] = (string) $song->Steps->attributes()->Difficulty;
					}

					#filter songs
					if (!empty($filterattr) && !empty($filterval)) {
						switch ($filterattr) {
							case 'player':
								if ($HighScore->PlayerGuid == array_flip($profiles)[$filterval]) {
									$songs[] = $fsong;
								}
								break;
							case 'pack':
								if (strpos($song->attributes()->Dir, '/' . $filterval . '/')) {
									$songs[] = $fsong;
								}
								break;
							case 'difficulty':
								if ($song->Steps->attributes()->Difficulty == $filterval) {
									$songs[] = $fsong;
								}
								break;
							case 'grade':
								if ($HighScore->Grade == array_flip($grades)[$filterval]) {
									$songs[] = $fsong;
								}
								break;
						}
					} else {
						$songs[] = $fsong;
					}
				}
			}
		}
	}
	
	#sort filter lists
	asort($profiles);
	sort($packs);
	sort($difficulties);
	ksort($grades);
	
	#sort songs
	usort($songs, function($a, $b) {
		$sort = filter_input(INPUT_GET, 'sort');
		switch ($sort) {
			case 'player':
				$cmpa = $_SESSION['profiles'][$a->player];
				$cmpb = $_SESSION['profiles'][$b->player];
				break;
			case 'pack':
			case 'title':
				$cmpa = $a->{$sort};
				$cmpb = $b->{$sort};
				break;
			case 'difficulty':
				$diffs = array('Challenge' => 1, 'Hard' => 2, 'Medium' => 3, 'Easy' => 4);
				$cmpa = $diffs[$a->difficulty];
				$cmpb = $diffs[$b->difficulty];
				break;
			case 'grade':
				$cmpa = $b->grade->percentage;
				$cmpb = $a->grade->percentage;
				break;
			default:
				if (isset($a->{$sort})) {
					$cmpa = $b->{$sort};
					$cmpb = $a->{$sort};
				} else {
					$cmpa = $b->date;
					$cmpb = $a->date;
				}
		}
		return $cmpa === $cmpb ? ($a->title === $b->title ? 0 : ($a->title > $b->title ? 1 : -1)) : ($cmpa > $cmpb ? 1 : -1);
	});
	
	#page controls
	$page = !empty(filter_input(INPUT_GET, 'page')) ? (int) filter_input(INPUT_GET, 'page') : 1;
	$sort = !empty(filter_input(INPUT_GET, 'sort')) ? filter_input(INPUT_GET, 'sort') : 'date';
	$pageSize = 25;
	$last = (int) ceil(count($songs) / $pageSize);
	$page = ($page > $last && !empty($last)) ? $last : $page;
	$start = ($page * $pageSize) - $pageSize;
	$end = ($page * $pageSize) >= count($songs) ? (count($songs)) : ($page * $pageSize);
	$prev = ($page - 1) >= 1 ? $page - 1 : 1;
	$next = ($page + 1) <= $last ? $page + 1 : $last;
	
	#start song stats
	echo '<table><thead><tr>
	<th class="date"><a class="' . ($sort === 'date' ? 'sorted' : '') . '" href=?page=' . $page . '&sort=date&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>Time Played</a></th>
	<th class="song"><a class="' . ($sort === 'title' ? 'sorted' : '') . '" href=?page=' . $page . '&sort=title&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>Song Title</a></th>
	<th class="difficulty"><a class="' . ($sort === 'difficulty' ? 'sorted' : '') . ($filterattr === 'difficulty' && !empty($filterval) ? ' filtered' : '') . '" href=?page=' . $page . '&sort=difficulty&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>Difficulty</a> <img src="/img/filter-icon-2.png" class="filter-button" title="Filter" filter="difficulty" /></th>
	<th class="score"><a class="' . ($sort === 'Score' ? 'sorted' : '') . '" href=?page=' . $page . '&sort=Score&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>Score</a></th>
	<th class="grade"><a class="' . ($sort === 'grade' ? 'sorted' : '') . ($filterattr === 'grade' && !empty($filterval) ? ' filtered' : '') . '" href=?page=' . $page . '&sort=grade&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>Grade</a> <img src="/img/filter-icon-2.png" class="filter-button" title="Filter" filter="grade" /></th>
	<th class="fegdwom"><a class="' . ($sort === 'f' ? 'sorted' : '') . '" href=?page=' . $page . '&sort=f&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>F</a></th><th class="fegdwom"><a class="' . ($sort === 'e' ? 'sorted' : '') . '" href=?page=' . $page . '&sort=e&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>E</a></th><th class="fegdwom"><a class="' . ($sort === 'g' ? 'sorted' : '') . '" href=?page=' . $page . '&sort=g&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>G</a></th><th class="fegdwom"><a class="' . ($sort === 'd' ? 'sorted' : '') . '" href=?page=' . $page . '&sort=d&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>D</a></th><th class="fegdwom"><a class="' . ($sort === 'wo' ? 'sorted' : '') . '" href=?page=' . $page . '&sort=wo&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>WO</a></th><th class="fegdwom"><a class="' . ($sort === 'm' ? 'sorted' : '') . '" href=?page=' . $page . '&sort=m&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>M</a></th>
	<th class="plays"><a class="' . ($sort === 'plays' ? 'sorted' : '') . '" href=?page=' . $page . '&sort=plays&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>Plays</a></th>	
	<th class="player"><a class="' . ($sort === 'player' ? 'sorted' : '') . ($filterattr === 'player' && !empty($filterval) ? ' filtered' : '') . '" href=?page=' . $page . '&sort=player&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>Player</a> <img src="/img/filter-icon-2.png" class="filter-button" title="Filter" filter="player" /></th>	
	</tr></thead><tbody>';
	for ($i = $start; $i < $end; $i++) {
		$song = $songs[$i];
		echo '<tr>';
		echo '<td class="date">' . $song->date->format('Y-m-d H:i:s') . '</td>';
		echo '<td class="song" title="Song Pack: ' . $song->pack . '">' . $song->title . '</td>';
		echo '<td class="difficulty">' . $song->difficulty . '</td>';
		echo '<td class="score">' . $song->score . '</td>';
		echo '<td class="grade">' . $song->grade->letter . ' (' . $song->grade->percentage . '%)</td>';
		$fegdwom = '<td class="fegdwom fantastic">' . str_pad($song->f, 3, '0', STR_PAD_LEFT) . '</td><td class="fegdwom excellent">' . str_pad($song->e, 3, '0', STR_PAD_LEFT) . '</td><td class="fegdwom great">' . str_pad($song->g, 3, '0', STR_PAD_LEFT) . '</td><td class="fegdwom decent">' . str_pad($song->d, 2, '0', STR_PAD_LEFT) . '</td><td class="fegdwom wayoff">' . str_pad($song->wo, 2, '0', STR_PAD_LEFT) . '</td><td class="fegdwom miss">' . str_pad($song->m, 2, '0', STR_PAD_LEFT) . '</td>';
		echo $fegdwom;
		echo '<td class="plays">' . $song->plays . '</td>';
		echo '<td class="player">' . $song->player . '</td>'; 		
		echo '</tr>';
	}
	echo '</tbody><tfoot><tr><td colspan="4">';
	
	#page control links
	if ($page === 1) {
		echo 'First | Prev | ';
	} else {
		echo '<a href="?page=1&sort=' . $sort . '&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '">First</a> | ';
		echo '<a href="?page=' . $prev . '&sort=' . $sort . '&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '">Prev</a> | ';
	} 
	if ($page === $last) {
		echo 'Next | Last';
	} else {
		echo '<a href="?page=' . $next . '&sort=' . $sort . '&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '">Next</a> | ';
		echo '<a href="?page=' . $last . '&sort=' . $sort . '&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '">Last</a>';
	}
	echo '</td>';
	if ($theme === 'light') {
		echo '<td class="switch" colspan="9"><a href="../index.php">Dark Theme</a></td></tr>';
	} else {
		echo '<td class="switch" colspan="9"><a href="../stats-light.php">Light Theme</a></td></tr>';
	}
	echo '<td class="render" colspan="13">';
$time_end = microtime(true);
$time = $time_end - $time_start;
echo 'Page created in ' . round($time, 5) . ' seconds.</tfoot></table></div>';
} 
else {
	exit('Failed to open stats.xml.');
}

?>
	<div id="filter_player" class="filter-menu">
		<select filter="player">
			<option value="">-Clear-</option>
			<?php foreach ($profiles as $player): ?>
			<option<?php echo $player === $filterval ? ' selected="selected"' : ''; ?>><?php echo $player; ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div id="filter_pack" class="filter-menu">
		<select filter="pack">
			<option value="">-Clear-</option>
			<?php foreach ($packs as $pack): ?>
			<option<?php echo $pack === $filterval ? ' selected="selected"' : ''; ?>><?php echo $pack; ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div id="filter_difficulty" class="filter-menu">
		<select filter="difficulty">
			<option value="">-Clear-</option>
			<?php foreach ($difficulties as $difficulty): ?>
			<option<?php echo $difficulty === $filterval ? ' selected="selected"' : ''; ?>><?php echo $difficulty; ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div id="filter_grade" class="filter-menu">
		<select filter="grade">
			<option value="">-Clear-</option>
			<?php foreach ($grades as $grade): ?>
			<option<?php echo $grade === $filterval ? ' selected="selected"' : ''; ?>><?php echo $grade; ?></option>
			<?php endforeach; ?>
		</select>
	</div>
</body>
</html>