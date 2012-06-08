<?php

/////////////////////////////////////////////////////////////////////
// License
/////////////////////////////////////////////////////////////////////

/*
*    Copyright (c) 2012 Kent Safranski (fluidbyte.net)
*
*    Permission is hereby granted, free of charge, to any person 
*    obtaining a copy of this software and associated documentation
*    files (the "Software"), to deal in the Software without 
*    restriction, including without limitation the rights to use, 
*    copy, modify, merge, publish, distribute, sublicense, and/or 
*    sell copies of the Software, and to permit persons to whom 
*    the Software is furnished to do so, subject to the following 
*    conditions:
*
*    The above copyright notice and this permission notice shall be
*    included in all copies or substantial portions of the Software.
*
*    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
*    EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES 
*    OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
*    NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT 
*    HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, 
*    WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING 
*    FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR 
*    OTHER DEALINGS IN THE SOFTWARE.
*/

/////////////////////////////////////////////////////////////////////
// Headers
////////////////////////////////////////////////////////////////////

    header('cache-control: no-cache, must-revalidate');
    header('content-type: application/json; charset=utf-8');

//////////////////////////////////////////////////////////////////////
// PHileAPI Controller File
//////////////////////////////////////////////////////////////////////
    
    /*
    * This file resides on the remote workspace and controls all file
    * and directory interactions
    */
    
//////////////////////////////////////////////////////////////////////
// Key(s)
//////////////////////////////////////////////////////////////////////
        
    $key[0] = "0123456789";
    
//////////////////////////////////////////////////////////////////////
// Timezone
//////////////////////////////////////////////////////////////////////

    date_default_timezone_set('America/Chicago');
    
//////////////////////////////////////////////////////////////////////
// Disable Errors
//////////////////////////////////////////////////////////////////////

    ini_set('display_errors', 0);
    
//////////////////////////////////////////////////////////////////////
// Verification
//////////////////////////////////////////////////////////////////////
    
    if(empty($_GET['key']) || !in_array($_GET['key'],$key)){ 
        exit('{"status":"001"}'); // Ker error
    }
    
//////////////////////////////////////////////////////////////////////
// Handlers
//////////////////////////////////////////////////////////////////////
    
    // Get Action
    if(!empty($_GET['action'])){ $action = $_GET['action']; }
    else{ exit('{"status":"002"}'); } // No action specified
    
    // Handle Action
    $phileapi = new PHileAPI();
    $phileapi->controller = array_shift(explode('?',basename($_SERVER['REQUEST_URI'], ".php")));
    
    switch($action){
        case 'index': $phileapi->index(); break;
        case 'open' : $phileapi->open(); break;      
        case 'create': $phileapi->create(); break;        
        case 'delete': $phileapi->delete(); break;        
        case 'modify': $phileapi->modify(); break;        
        case 'duplicate': $phileapi->duplicate(); break;       
        case 'upload': $phileapi->upload(); break;
        default: exit('{"status":"003"}'); // Unknown action      
    }
    
//////////////////////////////////////////////////////////////////////
// Actions
//////////////////////////////////////////////////////////////////////
    
    class PHileAPI {
    
        public $response    = "";
        public $root        = "";
        public $rel_path    = "";
        public $path        = "";
        public $type        = "";
        public $new_name    = "";
        public $content     = "";
        public $destination = "";
        public $upload      = "";
        public $controller  = "phileapi.php";
        
    //////////////////////////////////////////////////////////////////
    // Construct
    //////////////////////////////////////////////////////////////////
    
    public function __construct() {
        $this->root = dirname( __FILE__ );
        $this->rel_path = $_GET['path'];
        if($this->rel_path!="/"){ $this->rel_path .= "/"; } 
        $this->path = $this->root . $_GET['path'];
        // Create
        if(!empty($_GET['type'])){ $this->type = $_GET['type']; }
        // Modify\Create
        if(!empty($_GET['new_name'])){ $this->new_name = $_GET['new_name']; }
        if(!empty($_POST['content'])){ $this->content = stripslashes($_POST['content']); }
        // Duplicate
        if(!empty($_GET['destination'])){ $this->destination = $this->root . $_GET['destination']; }
    }

    //////////////////////////////////////////////////////////////////
    // INDEX (Returns list of files and directories)
    //////////////////////////////////////////////////////////////////
        
        public function index(){
        
            if(file_exists($this->path)){
                $index = array();
                if(is_dir($this->path) && $handle = opendir($this->path)){
                    while (false !== ($object = readdir($handle))) {
                        if ($object != "." && $object != ".." && $object != $this->controller) {
                            if(is_dir($this->path.'/'.$object)){ $type = "directory"; $size=0; }
                            else{ $type = "file"; $size=filesize($this->path.'/'.$object); }
                            $index[] = array(
                                "name"=>$this->rel_path . $object,
                                "type"=>$type,
                                "size"=>$size,
                                "mod"=>date("m-d-Y H:i:s",filemtime($path.$object))
                            );
                        }
                    }
                    closedir($handle);
                    $this->response = json_encode($index);
                }else{
                    $this->response = '{"status":"102"}'; // Not a directory
                }
            }else{
                $this->response = '{"status":"101"}'; // Does not exits
            }
                
            $this->respond();
        }
        
    //////////////////////////////////////////////////////////////////
    // OPEN (Returns the contents of a file)
    //////////////////////////////////////////////////////////////////
        
        public function open(){
            if(is_file($this->path)){
                $response = '{"content":' . json_encode(file_get_contents($this->path)) . '}';
                $this->response = $response;
            }else{
                $this->response = '{"status":"201"}'; // Not a file
            }

            $this->respond();
        }
    
    //////////////////////////////////////////////////////////////////
    // CREATE (Creates a new file or directory)
    //////////////////////////////////////////////////////////////////
        
        public function create(){
        
            // Create file
            if($this->type=="file"){
                if(!file_exists($this->path)){
                    if(fopen($this->path, 'w')){
                        // Write content
                        if($this->content){ fwrite($file, $this->content); }
                        fclose($file);
                        $this->response = '{"status":"300"}'; // Success
                    }else{
                        $this->response = '{"status":"301"}'; // Cannot create
                    }
                }else{
                    $this->response = '{"status":"302"}'; // File already exists
                }
            }
            
            // Create directory
            if($this->type=="directory"){
                if(mkdir($this->path)){
                    $this->response = '{"status":"300"}'; // Success
                }else{
                    $this->response = '{"status":"303"}'; // Cannot create
                }
            }
        
            $this->respond();        
        }
        
    //////////////////////////////////////////////////////////////////
    // DELETE (Deletes a file or directory (+contents))
    //////////////////////////////////////////////////////////////////
        
        public function delete(){
        
            function rrmdir($path){
                return is_file($path)?
                @unlink($path):
                @array_map('rrmdir',glob($path.'/*'))==@rmdir($path);
            }
            
            if(file_exists($this->path)){ rrmdir($this->path); 
            $this->response = '{"status":"400"}'; } // Success 
            else { $this->response = '{"status":"401"}'; } // Does not exist
            
            $this->respond();
        }
        
    //////////////////////////////////////////////////////////////////
    // MODIFY (Modifies a file name/contents or directory name)
    //////////////////////////////////////////////////////////////////
        
        public function modify(){
        
            // Change name
            if($this->new_name){
                $explode = explode('/',$this->path);
                array_pop($explode);
                $new_path = "/" . implode("/",$explode) . "/" . $this->new_name;
                if(!file_exists($new_path)){
                    if(@copy($this->path,$new_path)){
                        unlink($this->path);
                        $this->response = '{"status":"500"}'; // Success
                    }else{
                        $this->response = '{"status":"502"}'; // Could not rename
                    }
                }else{
                    $this->response = '{"status":"501"}'; // Already exists
                }
            }
            
            // Change content
            if($this->content){
                if(is_file($this->path)){
                    if($file = fopen($this->path, 'w')){ 
                        fwrite($file, $this->content);
                        fclose($file);
                        $this->response = '{"status":"500"}'; // Success
                    }else{
                       $this->response = '{"status":"504"}'; // Cannot write to file 
                    }
                }else{
                    $this->response = '{"status":"503"}'; // Not a file
                }
            }
            
            $this->respond();        
        }
        
    //////////////////////////////////////////////////////////////////
    // DUPLICATE (Creates a duplicate of the object - (cut/copy/paste)
    //////////////////////////////////////////////////////////////////
        
        public function duplicate(){
            
            if(!file_exists($this->path))
                { $this->response = '{"status":"601"}'; } // Invalid source
            if(!file_exists($this->destination))
                { $this->response = '{"status":"602"}'; } // Invalid destination
            
            function recurse_copy($src,$dst) { 
                $dir = opendir($src); 
                @mkdir($dst); 
                while(false !== ( $file = readdir($dir)) ) { 
                    if (( $file != '.' ) && ( $file != '..' )) { 
                        if ( is_dir($src . '/' . $file) ) { 
                            recurse_copy($src . '/' . $file,$dst . '/' . $file); 
                        } 
                        else { 
                            copy($src . '/' . $file,$dst . '/' . $file); 
                        } 
                    } 
                } 
                closedir($dir); 
            }
            
            if(is_file($this->path)){
                copy($this->path,$this->destination);
                $this->response = '{"status":"600"}'; // Success
            }else{
                recurse_copy($this->path,$this->destination);
                if(!$this->response){ $this->response = '{"status":"600"}'; } // Success
            }
  
            $this->respond();
        }
        
    //////////////////////////////////////////////////////////////////
    // UPLOAD (Handles uploads to the specified directory)
    //////////////////////////////////////////////////////////////////
        
        public function upload(){
        
            // Check that the path is a directory
            if(is_file($this->path)){ 
                $this->response = '{"status":"701"}'; // Path not a directory
            }else{
                // Handle upload
                $target = $this->path  . "/" . basename($_FILES['upload']['name']); 
                if(@move_uploaded_file($_FILES['upload']['tmp_name'], $target)) {
                    $this->response = '{"status":"700"}'; // Success    
                }else{
                    $this->response = '{"status":"702"}'; // Error uploading
                }          
            }

            $this->respond();        
        }
        
    //////////////////////////////////////////////////////////////////
    // RESPOND (Outputs data from all other functions)
    //////////////////////////////////////////////////////////////////
        
        public function respond(){ echo($this->response); }
    
    }
?>
