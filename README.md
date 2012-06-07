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