<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
 <head>
  <title>Template Override generieren</title>
  <!-- Latest compiled and minified CSS -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">

  <!-- Optional theme -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">

  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
 </head>
<?php

    function makeAll($dir, $mode = 0777, $recursive = true) {
        if( is_null($dir) || $dir === "" ){
            return FALSE;
        }

        if( is_dir($dir) || $dir === "/" ){
            return TRUE;
        }
        if( makeAll(dirname($dir), $mode, $recursive) ){
            return mkdir($dir, $mode);
        }
        return FALSE;
    }

    function smartCopy($source, $dest, $options=array('folderPermission'=>0755,'filePermission'=>0755))
    {
        $result=false;

        //For Cross Platform Compatibility
        if (!isset($options['noTheFirstRun'])) {
            $source=str_replace('\\','/',$source);
            $dest=str_replace('\\','/',$dest);
            $options['noTheFirstRun']=true;
        }

        if (is_file($source)) {
            if ($dest[strlen($dest)-1]=='/') {
                if (!file_exists($dest)) {
                    makeAll($dest,$options['folderPermission'],true);
                }
                $__dest=$dest."/".basename($source);
            } else {
                $__dest=$dest;
            }
            if (!file_exists($__dest)) {
                $result=copy($source, $__dest);
                chmod($__dest,$options['filePermission']);
            }
        } elseif(is_dir($source)) {
            if ($dest[strlen($dest)-1]=='/') {
                if ($source[strlen($source)-1]=='/') {
                    //Copy only contents
                } else {
                    //Change parent itself and its contents
                    $dest=$dest.basename($source);
                    @mkdir($dest);
                    chmod($dest,$options['filePermission']);
                }
            } else {
                if ($source[strlen($source)-1]=='/') {
                    //Copy parent directory with new name and all its content
                    @mkdir($dest,$options['folderPermission']);
                    chmod($dest,$options['filePermission']);
                } else {
                    //Copy parent directory with new name and all its content
                    @mkdir($dest,$options['folderPermission']);
                    chmod($dest,$options['filePermission']);
                }
            }

            $dirHandle=opendir($source);
            while($file=readdir($dirHandle))
            {
                if($file!="." && $file!="..")
                {
                    $__dest=$dest."/".$file;
                    $__source=$source."/".$file;
                    //echo "$__source ||| $__dest<br />";
                    if ($__source!=$dest) {
                        $result=smartCopy($__source, $__dest, $options);
                    }
                }
            }
            closedir($dirHandle);

        } else {
            $result=false;
        }
        return $result;
    }

    function compareDirectories( $path = '.', $changePath = '.', $level = 0 ){

        $ignore = array( 'cgi-bin', '.', '..' );
        $dh = @opendir( $path );

        while( false !== ( $file = readdir( $dh ) ) ){ // Loop through the directory
            if( !in_array( $file, $ignore ) ){
                if( is_dir( "$path/$file" ) ){
                    // Its a directory, so we need to keep reading down…
                    compareDirectories( "$path/$file", "$changePath/$file", ($level+1),$time );
                } else {
                    compareFiles("$path/$file", "$changePath/$file");
                }//elseif
            }//if in array
        }//while

        closedir( $dh );

    }

    function compareFiles( $path = '.', $changePath = '.' ){
        $vanilla_path = $path;
        $branch_path = $changePath;

        if( !file_exists($branch_path) || md5(file_get_contents($vanilla_path)) !== md5(file_get_contents($branch_path)) ){

            $tPath = str_replace($_REQUEST['modifiedtpl']."/",$_REQUEST['targetfolder'].'/',$vanilla_path);

            if($_REQUEST["displayonly"] == 1){
                echo "$vanilla_path >> $branch_path >> $tPath" . PHP_EOL;
            }else{
                makeAll(dirname($tPath));
                smartCopy($vanilla_path, $tPath);
            }
        }
        return true;
    }


    $aTemplates = array();
    foreach ( glob("application/views/**",GLOB_ONLYDIR) as $tplfolder ) {
        if(file_exists($tplfolder."/theme.php")){
            $aTemplates[] = basename($tplfolder);
        }
    }

    if($_REQUEST['fnc'] == 'create'){

        $modified = $_REQUEST['modifiedtpl'];
        $original = $_REQUEST['originaltpl'];

        if($_REQUEST["displayonly"] == 1)
          echo '<pre>';

        compareDirectories( 'application/views/'.$modified, 'application/views/'.$original);
        compareDirectories( 'out/'.$modified, 'out/'.$original);

        if($_REQUEST["displayonly"] == 1)
          echo '</pre>';


        $blCopied = true;

    }

?>

<body style="padding-top: 60px">

  <nav class="navbar navbar-inverse navbar-fixed-top">
    <div class="container">
      <div class="navbar-header">
        <a class="navbar-brand" href="http://www.aggrosoft.de">Aggrosoft Override Generator</a>
      </div>
    </div>
  </nav>

<div class="container">
<?php if($blCopied) :?>
        <div class="alert-message success">
        <p><strong>Kopiervorgang erfolgreich!</strong> Sie finden das neue Template jetzt im Ziel Ordner.</p>
        </div>
<?php endif; ?>
  <form action="tploverride.php" method="post">
    <input type="hidden" name="fnc" value="create">

    <div class="form-group">
      <label for="modifiedtpl">Ge&auml;ndertes Template</label>
      <select class="form-control" id="modifiedtpl" name="modifiedtpl">
          <?php foreach ($aTemplates as $sTemplate) : ?>
          <option value="<?php echo $sTemplate; ?>"><?php echo $sTemplate; ?></option>
          <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="originaltpl">Original Template</label>
      <select class="form-control" id="originaltpl" name="originaltpl">
          <?php foreach ($aTemplates as $sTemplate) : ?>
          <option value="<?php echo $sTemplate; ?>"><?php echo $sTemplate; ?></option>
          <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="targetfolder">Ziel Ordner</label>
      <input type="text" class="form-control" id="targetfolder" name="targetfolder">
    </div>

    <div class="form-group">
      <input type="checkbox" value="1" id="displayonly" name="displayonly">
      <label class="control-label" for="displayonly">Simulieren</label>
    </div>

    <div class="form-group">
      <button type="submit" class="btn btn-default">Start</button>
    </div>

  </form>

    </div>
  <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.22.3/js/jquery.tablesorter.min.js"></script>
  <script type="text/javascript">
      $(function(){
          $("table").tablesorter({ sortList: [[1,1]] });
      });
  </script>
 </body>
</html>
