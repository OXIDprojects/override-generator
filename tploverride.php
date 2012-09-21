<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
 <head>
  <title>Template Override generieren</title>
  <link rel="stylesheet" href="http://netdna.bootstrapcdn.com/twitter-bootstrap/2.1.0/css/bootstrap-combined.min.css">
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
        
        if( !file_exists($branch_path) || strcmp(file_get_contents($vanilla_path),file_get_contents($branch_path)) ){
            if($_REQUEST["displayonly"] == 1){
                echo "$vanilla_path >> $branch_path<br/>";
            }else{
                $tPath = str_replace($_REQUEST['modifiedtpl']."/",'',$vanilla_path);
            
                makeAll(dirname("out/".$_REQUEST['targetfolder']."/$tPath"));
                smartCopy($vanilla_path, "out/".$_REQUEST['targetfolder']."/$tPath");
            }
        }    
        return true;
    }
    
    

    
    $aTemplates = array();
    foreach ( glob("out/**",GLOB_ONLYDIR) as $tplfolder ) {
        if(file_exists($tplfolder."/theme.php")){
            $aTemplates[] = $tplfolder;
        }
    }
    
    if($_REQUEST['fnc'] == 'create'){
        
        compareDirectories( $_REQUEST['modifiedtpl'], $_REQUEST['originaltpl']);
        
        $blCopied = true;
        
    }
    
?>
 
<body style="padding-top: 60px">

 <div class="topbar-wrapper noprint" style="z-index: 5">
  <div class="topbar">
     <div class="fill">
         <div class="container">
             <h3><a href="#">Aggrosoft Override Generator</a></h3>
         </div>
     </div>
 </div>
 </div>

<div class="container">

        <div class="alert-message success">
        <p><strong>Kopiervorgang erfolgreich!</strong> Sie finden das neue Template jetzt im Ziel Ordner.</p>
        </div>

  <form action="tploverride.php" method="post">
      <input type="hidden" name="fnc" value="create">
    <fieldset>
      <legend>Custom Theme generieren</legend>
      <div class="clearfix">
        <label for="username">Ge&auml;ndertes Template</label>
        <div class="input">
          <select name="modifiedtpl">
              <?php foreach ($aTemplates as $sTemplate) : ?>
              <option value="<?php echo $sTemplate; ?>"><?php echo $sTemplate; ?></option>
              <?php endforeach; ?>
          </select>
        </div>
      </div><!-- /clearfix -->
      <div class="clearfix">
        <label for="username">Original Template</label>
        <div class="input">
          <select name="originaltpl">
              <?php foreach ($aTemplates as $sTemplate) : ?>
              <option value="<?php echo $sTemplate; ?>"><?php echo $sTemplate; ?></option>
              <?php endforeach; ?>
          </select>
        </div>
      </div><!-- /clearfix -->
      <div class="clearfix">
        <label for="username">Ziel Ordner (in out)</label>
        <div class="input">
          <input type="text" size="30" name="targetfolder" class="xlarge">
        </div>
      </div><!-- /clearfix -->
      <div class="clearfix">
        <label for="username">Simulieren</label>
        <div class="input">
            <div class="input-prepend">
                  <label class="add-on"><input type="checkbox" name="displayonly" value="1"></label>
                  <input class="small" readonly="readonly" value="Nur anzeigen" type="text">
              </div>
        </div>
      </div><!-- /clearfix -->            
      <div class="actions">
        <button class="btn primary" type="submit">Start</button>&nbsp;<button class="btn" type="reset">Cancel</button>
      </div>
    </fieldset>
  </form>

    </div>
  <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
  <script type="text/javascript" src="http://autobahn.tablesorter.com/jquery.tablesorter.min.js"></script>
  <script type="text/javascript">
      $(function(){
          $("table").tablesorter({ sortList: [[1,1]] });
      });
  </script>
 </body>
</html>