<!DOCTYPE html>
<html lang="en">
<head>
<title>MP3 File Converter</title>
<meta charset="utf-8">
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<META HTTP-EQUIV="Expires" CONTENT="-1">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.3/css/bootstrap.min.css" integrity="sha384-Zug+QiDoJOrZ5t4lssLdxGhVrurbmBWopoEl+M6BdEfwnCJZtKxi1KgxUyJq13dy" crossorigin="anonymous">
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.3/js/bootstrap.min.js" integrity="sha384-a5N7Y/aK3qNeh15eJKGWxsqtnX/wWdSZSKp+81YjTmS15nvnvxKHuzaWwXHDli+4" crossorigin="anonymous"></script>

<script>
.btn-file {
    position: relative;
    overflow: hidden;
}
.btn-file input[type=file] {
    position: absolute;
    top: 0;
    right: 0;
    min-width: 100%;
    min-height: 100%;
    font-size: 100px;
    text-align: right;
    filter: alpha(opacity=0);
    opacity: 0;
    outline: none;
    background: white;
    cursor: inherit;
    display: block;
}

#img-upload{
    width: 100%;
}

</script>

<?php

error_reporting(0);

$fileUploadSuccess = false;
$btnText = "Convert and Update MP3 File";
$uploading = $error = false;
$isMetadataRetrieved = false;
$title = $album_artist = $album = $year = $genre = "";
$target_dir = "uploads/";
$jquery_base_upload_dir = "server/php/files/";
$mp3File = "";

if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST' || strtoupper($_SERVER['REQUEST_METHOD']) == 'GET') {

    // When file is uploaded first time to retrieve metadata
    if(strlen($_POST["hiddenMP3file"]) > 0) {
        $isMetadataRetrieved = true;
    }
    
    if(isset($_GET["f"])) {
        //$_GET["f"] contains absolute path to file. Extract folder and file name
        $pathPieces = explode('/', $_GET["f"]);

        $jquery_upload_dir = $jquery_base_upload_dir . $pathPieces[sizeof($pathPieces) - 2];
        // Remove space and special characters from mp3 filename because ffmpeg creates problem
        $file = $pathPieces[sizeof($pathPieces) - 1];

        // retrieving extension
        $ext = explode('.',$file)[1];
        // Filename without extension
        $mp3FileWOext = explode('.',$file)[0];
        $mp3FileWOext = preg_replace("/[^a-zA-Z]+/", "", $mp3FileWOext);

        $mp3File = $target_dir . $mp3FileWOext . "." . $ext;
        
        if($ext != "mp3")
        {
            $error = true; $msg = "Upload MP3 files only!";
        }
        else {
            // Move mp3 file from jquery upload dir to uploads dir for ffmpeg
            shell_exec("mv -f " . $jquery_upload_dir . "/" . $file . " " . $mp3File);
            // Remove temporary created directory after moving file
            shell_exec("rm -rf " . $jquery_upload_dir);

            $fileUploadSuccess = true;
            // Get MetaData from uploaded file
            $timestamp = (new DateTime())->getTimestamp();
            $metadataFile = "metadata.txt"; //"metadata_" . $timestamp . ".txt";
            shell_exec("ffmpeg -i " . $mp3File . " -f ffmetadata -y " . $metadataFile);
            // Extract Image Metadata and save on disk
            $timestamp = (new DateTime())->getTimestamp();
            $metaImage = "CoverPhoto_". $timestamp . ".jpg";
            shell_exec("ffmpeg -i " . $mp3File . " -y " . $metaImage);
            // Sleep and wait for ffmpeg to write metadata file
            sleep(2);
            // When mp3 file does not contain Cover Photo, cover.jpg will not be created
            if(file_exists($metaImage) <= 0 ) {
                $metaImage = "";
            }
            // Extract Metadata values from metadata file                
            if(file_exists($metadataFile) >= 1 ) {
                $myfile = fopen($metadataFile, "r") or die("Unable to open file!");
                // Read file line by line
                while(!feof($myfile)) {
                    $line = fgets($myfile) ;
                    if (startsWith($line, "title")) {
                        $title = substr($line, strpos($line, "=")+1, strlen($line));
                    }
                    else if (startsWith($line, "album_artist")) {
                        $album_artist = substr($line, strpos($line, "=")+1, strlen($line));
                    }
                    else if (startsWith($line, "album")) {
                        $album = substr($line, strpos($line, "=")+1, strlen($line));
                    }
                    else if (startsWith($line, "year")) {
                        $year = substr($line, strpos($line, "=")+1, strlen($line));
                    }
                    else if (startsWith($line, "genre")) {
                        $genre = substr($line, strpos($line, "=")+1, strlen($line));
                    }
                }
                fclose($myfile);
                $btnText = "Convert and Update Details";
            }
        }
    }
    // Remove space and special characters from image filename because ffmpeg creates problem
    if(isset($_FILES["img"]["name"]) && strlen($_FILES["img"]["name"]) > 0) {
        $file = explode(".", $_FILES["img"]["name"]);
        // retrieving extension
        $ext = $file[1];
        // Filename without extension
        $imgFileWOext = preg_replace("/[^a-zA-Z]+/", "", $file[0]);

        $imgFile = $target_dir . $imgFileWOext . "." . $ext;

        $imgFileExtensions = array("jpg", "jpeg", "png");
        foreach($imgFileExtensions as $e) 
        {
            if($e == $ext) {
                $error = false;
                $msg = "";
                break;
            } else {
                $error = true; 
                $msg = "Upload only jpg, jpeg or png file for Cover Photo!";
            }
        }
    }
    // When no error and hidden field has mp3 file then convert bitrate and update metadata
    if(!$error && strlen($_POST["hiddenMP3file"]) > 0) {
        if(strlen($_FILES["img"]["name"]) > 0) {
            if (move_uploaded_file($_FILES["img"]["tmp_name"], $imgFile)) {
            } else {
                $error = true;
                $msg = "Sorry, there was an error uploading Image file.";
            }
        }
        $title = $_POST["title"];
        $author = $_POST["author"];
        $album = $_POST["album"];
        $year = $_POST["year"];
        $genre = $_POST["genre"];

        // Creating output file name
        $mp3File = $target_dir . $_POST["hiddenMP3file"];
        $mp3FileWOext = explode(".", $_POST["hiddenMP3file"])[0];
        $timestamp = (new DateTime())->getTimestamp();
        $outputfile = $mp3FileWOext . "_" . "128kbps" . "_" . $timestamp . ".mp3";
        $outputpath = $target_dir . $outputfile;

        //Command to convert to 128kbps bit rate
        //ffmpeg -i in.mp3 -ab 128k -y out.mp3
        //ffmpeg -i NumbLinkinPark.mp3 -ab 128k -y NumbLinkinPark_128k.mp3 && ffmpeg -i NumbLinkinPark_128k.mp3 -ab 64k -y NumbLinkinPark_64k.mp3
        $bitrate = "ffmpeg -i " . $mp3File . " -ab 128k -y " . $outputpath;
        
        // Generate final output filename
        $timestamp = (new DateTime())->getTimestamp();
        $outputfile = $mp3FileWOext . "_" . "128kbps" . "_" . $timestamp . "_meta.mp3";
        $outputpath2 = $target_dir . $outputfile;

        //ffmpeg -i out.mp3 -i img.jpg -map 0:0 -map 1:0 -codec copy -id3v2_version 3 -metadata title="My Song" -metadata author="Zaheer" 
        if(isset($imgFile)) {
            $metadata = 'ffmpeg -i '. $outputpath . ' -i ' . $imgFile. ' -map 0:0 -map 1:0 -codec copy -id3v2_version 3' . 
            ' -metadata title="' . $title . '"' .
            ' -metadata album_artist="' . $author . '"' .
            ' -metadata artist="' . $author . '"' .
            ' -metadata album="' . $album . '"' .
            ' -metadata year="' . $year . '"' .
            ' -metadata date="' . $year . '"' .
            ' -metadata genre="' . $genre . '"' .
            ' -metadata:s:v comment="Cover (front)"'.
            ' -y ' . $outputpath2;
        } 
        else if(isset($_POST["hiddenMetaImage"])) {
            $metadata = 'ffmpeg -i '. $outputpath . ' -i ' . $_POST["hiddenMetaImage"] . ' -map 0:0 -map 1:0 -codec copy -id3v2_version 3' . 
            ' -metadata title="' . $title . '"' .
            ' -metadata album_artist="' . $author . '"' .
            ' -metadata artist="' . $author . '"' .
            ' -metadata album="' . $album . '"' .
            ' -metadata year="' . $year . '"' .
            ' -metadata date="' . $year . '"' .
            ' -metadata genre="' . $genre . '"' .
            ' -metadata:s:v comment="Cover (front)"'.
            ' -y ' . $outputpath2;
        }
        else { // Change Metadata only (without image)
            $metadata = 'ffmpeg -i '. $outputpath . 
            ' -metadata title="' . $title . '"' .
            ' -metadata album_artist="' . $author . '"' .
            ' -metadata artist="' . $author . '"' .
            ' -metadata album="' . $album . '"' .
            ' -metadata year="' . $year . '"' .
            ' -metadata date="' . $year . '"' .
            ' -metadata genre="' . $genre . '"' .
            ' -y ' . $outputpath2;
        }

        // Change bitrate and metadata in sequencial order one after another
        $twoShellCommands = $bitrate . " && " . $metadata;
        $downloadableFile = $outputpath2;
        
        //ffmpeg -i uploads/RickyMartinPrivateEmotion.mp3 -ab 128k -y uploads/RickyMartinPrivateEmotion_128kbps_1538210205.mp3 && ffmpeg -i uploads/RickyMartinPrivateEmotion_128kbps_1538210205.mp3 -i uploads/rickymartin.png -map 0:0 -map 1:0 -codec copy -id3v2_version 3 -metadata title="Ricky Martin by zHs" -metadata author="Ricky" -metadata album="Private Emotions" -metadata year="2001" -metadata genre="Romantic" -metadata:s:v comment="Cover (front)" -y uploads/RickyMartinPrivateEmotion_128kbps_meta.mp3
        shell_exec($twoShellCommands);
        // Remove meta file
        shell_exec("rm " . $metaImage);
    }
}

function startsWith($haystack, $needle)
{
     $length = strlen($needle);
     return (substr($haystack, 0, $length) === $needle);
}

?>

</head>
    
<body style="margin:10px; padding:10px">
    <div class="container col-sm-6">
        <h3>Convert MP3 Files to 128Kbps</h3>
        <form name="mp3form" action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="hiddenMP3file" id="hiddenMP3file" value="<?php echo explode("/",$mp3File)[1] ?>" />

        <?php if ($error) { ?>
            <div class="alert alert-danger">
                <strong><?php  echo $msg; ?></strong>
            </div>
        <?php } ?>

        <?php
        if(isset($downloadableFile)) {
        ?>
            <div class="alert alert-success">
                <div class="col-sm-10">
                <a href=<?php echo $downloadableFile ?> download>Download MP3 File</a>
                </div>
            </div>
        <?php
        }
        ?>
        <div class="form-group">
            <p><h4>Step 2:</h4></p>
            <label for="img"><b>Upload Cover Photo</b></label>
            <div class="input-group">
                <span class="input-group-btn">
                <input type="file" id="img" name="img" >
                <?php if(strlen($metaImage) > 0) { ?>
                    <img id='img-upload' style="width: 100px;height: 100px" src=<?php echo $metaImage ?>  />
                    <input type="hidden" id="hiddenMetaImage" name="hiddenMetaImage" value="<?php echo $metaImage ?>" >
                <?php } else { ?>
                    <img id='img-upload' style="width: 100px;height: 100px" />
                <?php } ?>
                </span>                
            </div>
        </div>

        <div class="form-group row">
            <label for="title" class="col-sm-2 col-form-label">Title</label>
            <div class="col-sm-10">
            <input type="text" class="form-control" id="title" name="title" value="<?php echo $title ?>"  placeholder="Title">
            </div>
        </div>

        <div class="form-group row">
            <label for="author" class="col-sm-2 col-form-label">Artist</label>
            <div class="col-sm-10">
            <input type="text" class="form-control" id="author" name="author" value="<?php echo $album_artist ?>" placeholder="Album Artist">
            </div>
        </div>
        
        <div class="form-group row">
            <label for="album" class="col-sm-2 col-form-label">Album</label>
            <div class="col-sm-10">
            <input type="text" class="form-control" id="album" name="album" value="<?php echo $album ?>" placeholder="Album">
            </div>
        </div>        

        <div class="form-group row">
            <label for="year" class="col-sm-2 col-form-label">Year</label>
            <div class="col-sm-10">
            <input type="text" class="form-control" id="year" name="year" value="<?php echo $year ?>" placeholder="Year">
            </div>
        </div>

        <div class="form-group row">
            <label for="genre" class="col-sm-2 col-form-label">Genre</label>
            <div class="col-sm-10">
            <input type="text" class="form-control" id="genre" name="genre" value="<?php echo $genre ?>" placeholder="Genre">
            </div>
        </div>
        <div class="form-group row">
            <div class="col-sm-10">
            <?php if(isset($downloadableFile)) { ?>
                <a href="index.html" class="btn btn-primary" role="button">Convert Another File</a>
            <?php } else { ?>
                <input type="submit" id="btn" class="btn btn-primary" value="<?php echo $btnText ?>" >
            <?php } ?>
            </div>
        </div>
        </form>
    </div>
    <br />
<?php
if(isset($downloadableFile)) {
?>
<!-- Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLongTitle">Your File is Ready!</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
      Your MP3 file is ready to Download!
      </div>
      <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      <a href=<?php echo $downloadableFile ?> download>
        <button type="button" class="btn btn-primary">Download</button>
        </a>
      </div>
    </div>
  </div>
</div>

<?php
}
echo "<script type='text/javascript'>
$(document).ready(function(){
$('#myModal').modal('show');
});
</script>";
?>

<script>

$(document).ready( function() {
    $('#btn').click(function(){
        $(this).attr('disabled');
    });

    $(document).on('change', '.btn-file :file', function() {
    var input = $(this),
        label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
    input.trigger('fileselect', [label]);
    });

    $('.btn-file :file').on('fileselect', function(event, label) {
        
        var input = $(this).parents('.input-group').find(':text'),
            log = label;
        
        if( input.length ) {
            input.val(log);
        } else {
            if( log ) alert(log);
        }
    
    });
    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function (e) {
                $('#img-upload').attr('src', e.target.result);
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }

    $("#img").change(function(){
        readURL(this);
    });
});
</script>
</body>
</html>