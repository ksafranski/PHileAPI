#PHileAPI

Simple PHP API framework for working with the filesystem

##Usage

The API can be called by providing **key** (GET) an **action** (GET) and **path** (GET) parameters

###Index

**index** returns a JSON formatted array of the files in the **path** (directory)

**Example** : [URI]/phileapi.php?key=[KEY]&action=index&path=[PATH]

###Open

**open** returns the contents of a file provided in the **path** (file)

**Example** : [URI]/phileapi.php?key=[KEY]&action=open&path=[PATH]

###Create

**create** creates a file or directory at the **path** specified
This function requires the **type** (GET) as either 'file' or 'directory'

**Example** : [URI]/phileapi.php?key=[KEY]&action=create&path=[PATH]&type=[TYPE]

###Delete

**delete** removes a file or (recursively) a directory based on provided **path**

**Example** : [URI]/phileapi.php?key=[KEY]&action=delete&path=[PATH]

###Modify

**modify** is used to change name of file/directory or contents of file based on provided **path**

**Example (Rename)** : [URI]/phileapi.php?key=[KEY]&action=modify&path=[PATH]&new_name=[NEW_NAME]
**Example (Contents)** : [URI]/phileapi.php?key=[KEY]&action=modify&path=[PATH]    (POST='content')

###Duplicate

**duplicate** is used to create a copy of the file or directory in **destination**

**Example** : [URI]/phileapi.php?key=[KEY]&action=duplicate&path=[PATH]&destination=[DESTINATION]

###Upload

**upload** allows posting files to the server at the provided **path** (directory)

**Example** : [URI]/phileapi.php?key=[KEY]&action=upload&path=[PATH]    (FILES='upload')