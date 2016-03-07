<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
<title>Fenrirthviti's DDR Machine Stats</title>
<link rel="stylesheet" href="/css/style.css" type="text/css">
<script type="text/javascript" src="js/jquery-1.12.0.min.js"></script>
<script type="text/javascript" src="js/helper-functions.js"></script>
<script type="text/javascript" src="js/filter-menus.js"></script>
</head>
<body>
<?php
error_reporting(E_ALL); ini_set('display_errors', 1);
if (file_exists('stats.xml')) { 
	$xml = simplexml_load_file('stats.xml'); 

	#start machine total stats
	echo '<div id="wrapper"><table><tbody><tr>
	<th>Last Session Date</th>
	<th>Total Sessions</th>
	<th>Total Notes Hit</th>
	<th>Total Songs Played (Passed)</th></tr>';
	$machine = $xml->GeneralData;
	echo '<tr>';
	echo '<td class="center">' . $machine->LastPlayedDate . '</td>';
	echo '<td class="center">' . $machine->TotalSessions . '</td>';
	echo '<td class="center">' . $machine->TotalTapsAndHolds . '</td>';
	echo '<td class="center">' . $machine->NumTotalSongsPlayed . ' (' . $machine->NumStagesPassedByPlayMode->Regular . ')</td>';
	echo '</tr></tbody></table>';

	#profile list
	$profiles = array(
		'4c860f3932e17f1f' => 'Joel',
		'7d8c471b4187fea2' => 'Alyssa',
		'9232f9ff7f1af5a4' => 'Mike'
	);
	$_SESSION['profiles'] = $profiles;
	
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
			list($dir, $songPack, $songName) = explode('/', $song->attributes()->Dir);
			if (!in_array($songPack, $packs)) {
				$packs[] = $songPack;
			}
			if (!in_array($song->Steps->attributes()->Difficulty, $difficulties)) {
				$difficulties[] = (string) $song->Steps->attributes()->Difficulty;
			}
			if (!empty($filterattr) && !empty($filterval)) {
				switch ($filterattr) {
					case 'player':
						$playernames = array_flip($profiles);
						if ($song->Steps->HighScoreList->HighScore->PlayerGuid == $playernames[$filterval]) {
							$songs[] = $song;
						}
						break;
					case 'pack':
						if (strpos($song->attributes()->Dir, '/' . $filterval . '/')) {
							$songs[] = $song;
						}
						break;
					case 'difficulty':
						if ($song->Steps->attributes()->Difficulty == $filterval) {
							$songs[] = $song;
						}
						break;
					case 'grade':
						$tiers = array_flip($grades);
						if ($song->Steps->HighScoreList->HighScore->Grade == $tiers[$filterval]) {
							$songs[] = $song;
						}
						break;
				}
			} else {
				$songs[] = $song;
			}
		}
	}
	asort($profiles);
	sort($packs);
	sort($difficulties);
	ksort($grades);
	
	#sort songs
	usort($songs, function($a, $b) {
		$sort = filter_input(INPUT_GET, 'sort');
		list($dir, $songPacka, $songNamea) = explode('/', $a->attributes()->Dir);
		list($dir, $songPackb, $songNameb) = explode('/', $b->attributes()->Dir);
		switch ($sort) {
			case 'player':
				$cmpa = $_SESSION['profiles'][(string) $a->Steps->HighScoreList->HighScore->PlayerGuid];
				$cmpb = $_SESSION['profiles'][(string) $b->Steps->HighScoreList->HighScore->PlayerGuid];
				break;
			case 'pack':
				$cmpa = $songPacka;
				$cmpb = $songPackb;
				break;
			case 'title':
				$cmpa = $songNamea;
				$cmpb = $songNameb;
				break;
			case 'plays':
				$cmpa = (int) $b->Steps->HighScoreList->NumTimesPlayed;
				$cmpb = (int) $a->Steps->HighScoreList->NumTimesPlayed;
				break;
			case 'difficulty':
				$diffs = array('Challenge' => 1, 'Hard' => 2, 'Medium' => 3, 'Easy' => 4);
				$cmpa = $diffs[(string) $a->Steps->attributes()->Difficulty];
				$cmpb = $diffs[(string) $b->Steps->attributes()->Difficulty];
				break;
			default:
				if (isset($a->Steps->HighScoreList->HighScore->{$sort})) {
					$cmpa = (float) $b->Steps->HighScoreList->HighScore->{$sort};
					$cmpb = (float) $a->Steps->HighScoreList->HighScore->{$sort};
				} elseif(isset($a->Steps->HighScoreList->HighScore->TapNoteScores->{$sort})) {
					$cmpa = (float) $b->Steps->HighScoreList->HighScore->TapNoteScores->{$sort};
					$cmpb = (float) $a->Steps->HighScoreList->HighScore->TapNoteScores->{$sort};
				} else {
					$cmpa = new DateTime($b->Steps->HighScoreList->HighScore->DateTime);
					$cmpb = new DateTime($a->Steps->HighScoreList->HighScore->DateTime);
				}
		}
		return $cmpa === $cmpb ? 0 : ($cmpa > $cmpb ? 1 : -1);
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
	$next = ($page + 1) <= 10 ? $page + 1 : 10;
	
	#start song stats
	echo '<table><tbody><tr>
	<th><a class="' . ($sort === 'date' ? 'sorted' : '') . '" href=?page=' . $page . '&sort=date&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>Time Played</a></th>
	<th><a class="' . ($sort === 'player' ? 'sorted' : '') . ($filterattr === 'player' && !empty($filterval) ? ' filtered' : '') . '" href=?page=' . $page . '&sort=player&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>Player</a> <img src="/img/filter-icon.png" class="filter-button" title="Filter" filter="player" /></th>
	<th><a class="' . ($sort === 'pack' ? 'sorted' : '') . ($filterattr === 'pack' && !empty($filterval) ? ' filtered' : '') . '" href=?page=' . $page . '&sort=pack&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>Song Pack</a> <img src="/img/filter-icon.png" class="filter-button" title="Filter" filter="pack" /></th>
	<th><a class="' . ($sort === 'title' ? 'sorted' : '') . '" href=?page=' . $page . '&sort=title&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>Song Title</a></th>
	<th><a class="' . ($sort === 'plays' ? 'sorted' : '') . '" href=?page=' . $page . '&sort=plays&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '># Played</a></th>
	<th><a class="' . ($sort === 'difficulty' ? 'sorted' : '') . ($filterattr === 'difficulty' && !empty($filterval) ? ' filtered' : '') . '" href=?page=' . $page . '&sort=difficulty&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>Difficulty</a> <img src="/img/filter-icon.png" class="filter-button" title="Filter" filter="difficulty" /></th>
	<th><a class="' . ($sort === 'Score' ? 'sorted' : '') . '" href=?page=' . $page . '&sort=Score&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>Score</a></th>
	<th><a class="' . ($sort === 'PercentDP' ? 'sorted' : '') . ($filterattr === 'grade' && !empty($filterval) ? ' filtered' : '') . '" href=?page=' . $page . '&sort=PercentDP&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>Grade</a> <img src="/img/filter-icon.png" class="filter-button" title="Filter" filter="grade" /></th>
	<th><a class="' . ($sort === 'W1' ? 'sorted' : '') . '" href=?page=' . $page . '&sort=W1&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>F</a> / <a class="' . ($sort === 'W2' ? 'sorted' : '') . '" href=?page=' . $page . '&sort=W2&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>E</a> / <a class="' . ($sort === 'W3' ? 'sorted' : '') . '" href=?page=' . $page . '&sort=W3&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>G</a> / <a class="' . ($sort === 'W4' ? 'sorted' : '') . '" href=?page=' . $page . '&sort=W4&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>D</a> / <a class="' . ($sort === 'W5' ? 'sorted' : '') . '" href=?page=' . $page . '&sort=W5&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>WO</a> / <a class="' . ($sort === 'Miss' ? 'sorted' : '') . '" href=?page=' . $page . '&sort=Miss&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '>M</a></th>
	</tr>';	
	for ($i = $start; $i < $end; $i++) {
		$song = $songs[$i];
		list($dir, $songPack, $songName) = explode('/',  $song->attributes()->Dir   );
		echo '<tr>';
		echo '<td>' . $song->Steps->HighScoreList->HighScore->DateTime . '</td>';
		echo '<td class="center">' . $profiles[(string) $song->Steps->HighScoreList->HighScore->PlayerGuid] . '</td>'; 
		echo '<td>' . $songPack . '</td>';
		echo '<td>' . $songName . '</td>';
		echo '<td class="center">' . $song->Steps->HighScoreList->NumTimesPlayed . '</td>';
		echo '<td class="center">' . $song->Steps->attributes()->Difficulty . '</td>';
		echo '<td class="center">' . $song->Steps->HighScoreList->HighScore->Score . '</td>';
		$letter = $grades[(string) $song->Steps->HighScoreList->HighScore->Grade];
		$percentage = number_format(round((float) $song->Steps->HighScoreList->HighScore->PercentDP * 100, 2), 2);		
		$grade = $letter . ' (' . $percentage . '%)';
		echo '<td class="center">' . $grade . '</td>';
		$fegdwom = '<span class="fantastic">' . str_pad($song->Steps->HighScoreList->HighScore->TapNoteScores->W1, 3, '0', STR_PAD_LEFT) . '</span> / <span class="excellent">' . str_pad($song->Steps->HighScoreList->HighScore->TapNoteScores->W2, 3, '0', STR_PAD_LEFT) . '</span> / <span class="great">' . str_pad($song->Steps->HighScoreList->HighScore->TapNoteScores->W3, 3, '0', STR_PAD_LEFT) . '</span> / <span class="decent">' . str_pad($song->Steps->HighScoreList->HighScore->TapNoteScores->W4, 2, '0', STR_PAD_LEFT) . '</span> / <span class="wayoff">' . str_pad($song->Steps->HighScoreList->HighScore->TapNoteScores->W5, 2, '0', STR_PAD_LEFT) . '</span> / <span class="miss">' . str_pad($song->Steps->HighScoreList->HighScore->TapNoteScores->Miss, 2, '0', STR_PAD_LEFT) . '</span>';
		echo '<td class="center">' . $fegdwom . '</td>';
		echo '</tr>';
	}
	echo '</tbody></table><br />';
	
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
		echo '<a href="?page=' . $last . '&sort=' . $sort . '&filterattr=' . $filterattr . '&filterval=' . urlencode($filterval) . '">Last</a></div>';
	}
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