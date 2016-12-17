<?php
/* **********START CONFIGURATION ********** */

$RootDir = "./camera/";
$Fuzzy = "2 minutes"; // Filter for possible related files; ± 2 minutes 00 seconds.
$DateFormat = "Y-m-d H:i:s"; //FOR DISPLAY ONLY See PHP's date formatting for alternatives
$IMGFormat = "jpg"; //file extension of auto-generated images
$VIDFormat = "mp4"; //file extension of auto-generated videos
$FileCruft = "01_"; //If your generated-files contain any prefix prior to the date code enter it here.

/* **********END CONFIGURATION ********** */
clearstatcache();
$Cameras = array_values(array_diff(scandir($RootDir), array('..', '.', 'index.php')));

$CameraFiles = array();

//Returns a list of files in a given directory ($outerDir); matching any filters ($x)
function getAllFiles( $outerDir , $x){ 
    $dirs = array_values(array_diff( scandir( $outerDir ), array( ".", ".." ) )); 
    $dir_array = array(); 
    foreach( $dirs as $d ){ 
        if( !is_dir($outerDir."/".$d) && filesize($outerDir."/".$d)  ){ 
			if (($x)?ereg($x.'$',$d):1) $dir_array[] = pathinfo($d, PATHINFO_FILENAME); 
			} 
    }
	asort($dir_array);
    return $dir_array; 
}

//Loads all files into a nested array
//Format of $CameraFiles[CAMERA_NAME][NUMERICAL_KEY] = filename (no ext)
function getCameraFiles($Cameras, $RootDir){
	for ($i = 0; $i < count($Cameras); $i++){
			$tFiles = getAllFiles($RootDir.$Cameras[$i],$VIDFormat); 
			for ($x = 0; $x < count($tFiles); $x++){
				$CameraFiles[$Cameras[$i]][] = $tFiles[$x];;
			}
	}
	return $CameraFiles;
}

function scale_to_height ($filename, $targetheight) {
   $size = getimagesize($filename);
   $targetwidth = $targetheight * ($size[0] / $size[1]);
   return $targetwidth;
}
          
function scale_to_width ($filename, $targetwidth) {
   $size = getimagesize($filename);
   $targetheight = $targetwidth * ($size[1] / $size[0]);
   return $targetheight;
}

function dateFromFile($file){
	$file = ltrim($file, $FileCruft);
	$date = date_create_from_format('YmdHis',$file);
	return($date);
}

function css (){
	?><style>
	.video {
		width: 50%    !important;
		height: auto   !important;
	}
	.related {
		position: absolute;
		bottom: 20px;
	}
	.unlink {
	}
	</style>
	<?php 
}

$CameraFiles = getCameraFiles($Cameras, $RootDir);

/* **********START DISPLAY ********** */

if($_GET['index'] == "" || !isset($_GET['index'])){
	css();
	?><a class='unlink' align='center' href="./?index=unlink&ALL=TRUE">DELETE ALL</a>
	<?php
	for ($i = 0; $i < count($Cameras); $i++){
		if(count($CameraFiles[$Cameras[$i]]) > 0){
			echo "<table>";
			echo "<th>". $Cameras[$i]." - ".count($CameraFiles[$Cameras[$i]])." </th><tr>";
			//Displays timestamp
			for($x = 0; $x < count($CameraFiles[$Cameras[$i]]); $x++){
				echo "<td>";
				$DateStamp = dateFromFile($CameraFiles[$Cameras[$i]][$x]);
				$tWidth = scale_to_width($RootDir.$Cameras[$i]."/".$CameraFiles[$Cameras[$i]][$x].".".$IMGFormat, 250);
				echo date_format($DateStamp, $DateFormat);
				echo "</td>";
			}
			echo "</tr><tr>";
			//displays screenshot with hyperlink
			for($x = 0; $x < count($CameraFiles[$Cameras[$i]]); $x++){
				$tWidth = scale_to_width($RootDir.$Cameras[$i]."/".$CameraFiles[$Cameras[$i]][$x].".".$IMGFormat, 250);
				echo "<td><a href='./?index=video&camera=".$Cameras[$i]."&video=".$CameraFiles[$Cameras[$i]][$x]."'><img src='".$RootDir.$Cameras[$i]."/".$CameraFiles[$Cameras[$i]][$x].".".$IMGFormat."' height='".$tWidth."' /></a></td>";
			}
			
			echo "</tr><tr>";
			//Quick Delete button
			for($x = 0; $x < count($CameraFiles[$Cameras[$i]]); $x++){
				echo "<td><a class='unlink' href='./?index=unlink&camera=".$Cameras[$i]."&video=".$CameraFiles[$Cameras[$i]][$x]."'>QUICK DELETE</a></td>";
			}

			echo "</tr>";
			echo "</table>";
		}
	}
}
elseif($_GET['index'] == "video"){
		if(is_file($RootDir.$_GET['camera']."/".$_GET['video'].".".$VIDFormat)){
			css();
			
			$DateStamp = dateFromFile($_GET['video']);
			$Future = clone $DateStamp;
			$Past = clone $DateStamp;
			
			echo date_format($DateStamp, $DateFormat)."<br />";
			
			?><video class="video" controls><source src="<?php echo $RootDir.$_GET['camera']."/".$_GET['video'].".".$VIDFormat?>" type="audio/mpeg"></video>
			<br />
			<align='center'><a href="./?index=unlink&camera=<?php echo $_GET['camera'];?>&video=<?php echo $_GET['video'];?>"> DELETE THIS</a></align>
			<div class="related">
			<?php
		
			$Future->modify("+".$Fuzzy);
			$Past->modify("-".$Fuzzy);
			
			for ($i = 0; $i < count($Cameras); $i++){
				if($Cameras[$i] != $_GET['camera']){
					for($x = 0; $x < count($CameraFiles[$Cameras[$i]]); $x++){
						$DateStamp = dateFromFile($CameraFiles[$Cameras[$i]][$x]);
						if($DateStamp < $Future && $DateStamp > $Past){
							$tWidth = scale_to_width($RootDir.$Cameras[$i]."/".$CameraFiles[$Cameras[$i]][$x].".".$IMGFormat, 250);
							$tWidth = scale_to_width($RootDir.$Cameras[$i]."/".$CameraFiles[$Cameras[$i]][$x].".".$IMGFormat, 250);
							echo "<a href='./?index=video&camera=".$Cameras[$i]."&video=".$CameraFiles[$Cameras[$i]][$x]."'><img src='".$RootDir.$Cameras[$i]."/".$CameraFiles[$Cameras[$i]][$x].".".$IMGFormat."' height='".$tWidth."' /></a>";
						}
					}
				}
			}
			?>
			</div>
			<?php
		}
		else{ echo "INVALID VIDEO FILE";}
}
elseif($_GET['index'] == 'unlink' && isset($_GET['camera']) && isset($_GET['video'])){
	@unlink($RootDir.$_GET['camera']."/".$_GET['video'].".".$VIDFormat);
	@unlink($RootDir.$_GET['camera']."/".$_GET['video'].".".$IMGFormat);
	header('Location: ./');
}
elseif($_GET['index'] == 'unlink' && $_GET['ALL'] == TRUE){
	if($_GET['serious'] == TRUE){
		$dFiles = array();
		
		for ($i = 0; $i < count($Cameras); $i++){
			$mFiles = getAllFiles($RootDir.$Cameras[$i],$VIDFormat); 
			$jFiles = getAllFiles($RootDir.$Cameras[$i],$IMGFormat); 
			for ($x = 0; $x < count($mFiles); $x++){
				
				//Only files older than 5 minutes to ensure no strays
				$mtime = filemtime($RootDir.$Cameras[$i]."/".$mFiles[$x].".".$VIDFormat);
				$now = time();
				if(($now - $mtime) >= 300){
					$dFiles[] = $RootDir.$Cameras[$i]."/".$mFiles[$x].".".$VIDFormat;
					$dFiles[] = $RootDir.$Cameras[$i]."/".$jFiles[$x].".".$IMGFormat;
				}
			}
		}
		for($i = 0; $i <= count($dFiles); $i++){
			@unlink ($dFiles[$i]);
			header('Location: ./');
		}
	}
	else{
		?>
		<div class='unlink'>ARE YOU CERTAIN? THIS CANNOT BE REVERSED</DIV>
		<div class='unlink'> (This will only delete files last modified > 5 minutes ago)</div>
		<div class='unlink'><a href='./?index=unlink&ALL=TRUE&serious=TRUE'>ABSOLUTELY</a></div>
		<a href='./?index=FALSE'color='blue'>TAKE ME BACK</a>
		<?php
	}
}
else{header('Location: ./');}
?>