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

//////////////////////////////////////////////////////////////////////
// PHileAPI Controller File
//////////////////////////////////////////////////////////////////////
    
    /*
    * This file resides on the remote workspace and controls all file
    * and directory interactions
    */

/////////////////////////////////////////////////////////////////////
// Headers
////////////////////////////////////////////////////////////////////

    header('cache-control: no-cache, must-revalidate');
    header('content-type: application/json; charset=utf-8');
    
//////////////////////////////////////////////////////////////////////
// Key(s)
//////////////////////////////////////////////////////////////////////
        
    $key[0] = "0123456789";
    
//////////////////////////////////////////////////////////////////////
// Allowed IP's (blank for all-allowed)
//////////////////////////////////////////////////////////////////////

    $ips = array();
    
    // Ex: $ips = array("10.10.10.1","127.0.0.1",...);
    
//////////////////////////////////////////////////////////////////////
// Timezone
//////////////////////////////////////////////////////////////////////

    date_default_timezone_set('America/Chicago');
    
//////////////////////////////////////////////////////////////////////
// Disable Errors
//////////////////////////////////////////////////////////////////////

    ini_set('display_errors', 0);
    
//////////////////////////////////////////////////////////////////////
// Key Verification
//////////////////////////////////////////////////////////////////////
    
    if(empty($_GET['key']) || !in_array($_GET['key'],$key)){ 
        exit('{"status":"fail","data":{"error":"Invalid Key"}}');
    }
    
//////////////////////////////////////////////////////////////////////
// Check IPs
//////////////////////////////////////////////////////////////////////

    function getIP(){
        if (!empty($_SERVER['HTTP_CLIENT_IP'])){ 
            $ip=$_SERVER['HTTP_CLIENT_IP'];
        }elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
        }else{ $ip=$_SERVER['REMOTE_ADDR']; }
        return $ip;
    }
    
    if(!empty($ips) && !in_array(getIP(),$ips)){
        exit('{"status":"fail","data":{"error":"Invalid Request IP: '.getIP().'"}}');
    } 
    
//////////////////////////////////////////////////////////////////////
// Handlers
//////////////////////////////////////////////////////////////////////
    
    // Get Action
    if(!empty($_GET['action'])){ $action = $_GET['action']; }
    else{ exit('{"status":"fail","data":{"error":"No Action Specified"}}'); }
    
    // Handle Action
    $phileapi = new PHileAPI($_GET,$_POST);
    $phileapi->controller = array_shift(explode('?',basename($_SERVER['REQUEST_URI'], ".php")));
    
    switch($action){
        case 'index': $phileapi->index(); break;
        case 'open' : $phileapi->open(); break;      
        case 'create': $phileapi->create(); break;        
        case 'delete': $phileapi->delete(); break;        
        case 'modify': $phileapi->modify(); break;        
        case 'duplicate': $phileapi->duplicate(); break;       
        case 'upload': $phileapi->upload(); break;
        default: exit('{"status":"fail","data":{"error":"Unknown Action"}}');      
    }
    
//////////////////////////////////////////////////////////////////////
// Actions
//////////////////////////////////////////////////////////////////////
    
    class PHileAPI {
    
        public $root        = "";
        public $rel_path    = "";
        public $path        = "";
        public $type        = "";
        public $new_name    = "";
        public $content     = "";
        public $destination = "";
        public $upload      = "";
        public $controller  = "";
        
        // JSEND Return Contents
        public $status      = "";
        public $data        = "";
        public $message     = ""; 
        
    //////////////////////////////////////////////////////////////////
    // Construct
    //////////////////////////////////////////////////////////////////
    
    public function __construct($get,$post) {
        $this->root = dirname( __FILE__ );
        $this->rel_path = $get['path'];
        if($this->rel_path!="/"){ $this->rel_path .= "/"; } 
        $this->path = $this->root . $get['path'];
        // Create
        if(!empty($get['type'])){ $this->type = $get['type']; }
        // Modify\Create
        if(!empty($get['new_name'])){ $this->new_name = $get['new_name']; }
        if(!empty($post['content'])){ $this->content = stripslashes($post['content']); }
        // Duplicate
        if(!empty($get['destination'])){ $this->destination = $this->root . $get['destination']; }
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
                    $this->status = "success";
                    $this->data = '"index":' . json_encode($index);
                }else{
                    $this->status = "error";
                    $this->message = "Not A Directory";
                }
            }else{
                $this->status = "error";
                $this->message = "Path Does Not Exist";
            }
                
            $this->respond();
        }
        
    //////////////////////////////////////////////////////////////////
    // OPEN (Returns the contents of a file)
    //////////////////////////////////////////////////////////////////
        
        public function open(){
            if(is_file($this->path)){
                $this->status = "success";
                $this->data = '"content":' . json_encode(file_get_contents($this->path));
            }else{
                $this->status = "error";
                $this->message = "Not A File";
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
                        $this->status = "success";
                    }else{
                        $this->status = "error";
                        $this->message = "Cannot Create File";
                    }
                }else{
                    $this->status = "error";
                    $this->message = "Already Exists";
                }
            }
            
            // Create directory
            if($this->type=="directory"){
                if(mkdir($this->path)){
                    $this->status = "success";
                }else{
                    $this->status = "error";
                    $this->message = "Cannot Create Directory";
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
                $this->status = "success"; 
            }else{ 
                $this->status = "error";
                $this->message = "Path Does Not Exist";
            }
            
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
                        $this->status = "success";
                    }else{
                        $this->status = "error";
                        $this->message = "Could Not Rename";
                    }
                }else{
                    $this->status = "error";
                    $this->message = "Path Already Exists";
                }
            }
            
            // Change content
            if($this->content){
                if(is_file($this->path)){
                    if($file = fopen($this->path, 'w')){ 
                        fwrite($file, $this->content);
                        fclose($file);
                        $this->status = "success";
                    }else{
                       $this->status = "error";
                        $this->message = "Cannot Write File";
                    }
                }else{
                    $this->status = "error";
                    $this->message = "Not A File";
                }
            }
            
            $this->respond();        
        }
        
    //////////////////////////////////////////////////////////////////
    // DUPLICATE (Creates a duplicate of the object - (cut/copy/paste)
    //////////////////////////////////////////////////////////////////
        
        public function duplicate(){
            
            if(!file_exists($this->path)){ 
                $this->status = "error";
                $this->message = "Invalid Source";
            }
            
            if(!file_exists($this->destination)){ 
                $this->status = "error";
                $this->message = "Invalid Destination"; 
            }
            
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
                $this->status = "success";
            }else{
                recurse_copy($this->path,$this->destination);
                if(!$this->response){ $this->status = "success"; }
            }
  
            $this->respond();
        }
        
    //////////////////////////////////////////////////////////////////
    // UPLOAD (Handles uploads to the specified directory)
    //////////////////////////////////////////////////////////////////
        
        public function upload(){
        
            // Check that the path is a directory
            if(is_file($this->path)){ 
                $this->status = "error";
                $this->message = "Path Not A Directory";
            }else{
                // Handle upload
                $target = $this->path  . "/" . basename($_FILES['upload']['name']); 
                if(@move_uploaded_file($_FILES['upload']['tmp_name'], $target)) {
                    $this->status = "success";    
                }else{
                    $this->status = "error";
                    $this->message = "Upload Error";
                }          
            }

            $this->respond();        
        }
        
    //////////////////////////////////////////////////////////////////
    // RESPOND (Outputs data in JSON [JSEND] format)
    //////////////////////////////////////////////////////////////////
        
        public function respond(){ 
            
            // Success ///////////////////////////////////////////////
            if($this->status=="success"){
                if($this->data){
                    $json = '{"status":"success","data":{'.$this->data.'}}';
                }else{
                    $json = '{"status":"success","data":null}';
                }
            
            // Error /////////////////////////////////////////////////
            }else{
                $json = '{"status":"error","message":"'.$this->message.'"}';
            }
            
            // Output ////////////////////////////////////////////////
            echo($json); 
            
        }
    
    }
?>

