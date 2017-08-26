<?php
namespace cwl;

class files {

	static function loadCSV($file){
        // Load a CSV file into an associative array and return it
        $return = array();
        $csvfile = fopen($file,'r');
        if ($csvfile){
            $fields = fgetcsv($csvfile);
            while($record = fgetcsv($csvfile)){
                $newrecord = array();
                for ($index = 0; $index < count($record); $index++) {
                    if(isset($fields[$index])){
                        // $newrecord[$fields[$index]] = $record[$index];    
                        // $newrecord[$fields[$index]] = utf8_encode($record[$index]);    
                        $newrecord[$fields[$index]] = iconv("CP1251", "UTF-8", $record[$index]);    
                    }
                }
                $return[] = $newrecord;
            }
        } else {
            // Send back a "FALSE" if we can't read the file
            $return = FALSE;
        }
        return $return;
    }

	static function saveFiles($to,&$data = array()){
		// Grab $_FILES array
		$files = $_FILES;
		
		// Make sure $to has / on the end
		$to .= '/';
		$to = str_ireplace('//','/',$to);
		
		// Loop through the files, assuming we have somewhere to put them
		if(file_exists($to)) { 
			foreach($files as $field => $file){
				if(is_array($file['name'])){ 
					foreach($file['name'] as $key => $fileArray){
						if($file['error'][$key] == 0){
							$newfilename = $to . "{$file['name'][$key]}";
							if(file_exists($newfilename)){
								$newfilename = $to . uniqid() . "-{$file['name'][$key]}";
							}
							move_uploaded_file($file['tmp_name'][$key], $newfilename);
							if(!isset($data[$field])){ $data[$field] = array();}
							$data[$field][] = $newfilename;
						}	
					}
				} else {
					if($file['error'] == 0){
						$newfilename = $to . "{$file['name']}";
						if(file_exists($newfilename)){
							$newfilename = $to . uniqid() . "-{$file['name']}";
						}
						move_uploaded_file($file['tmp_name'], $newfilename);
						if(isset($data[$field])){
							if(is_array($data[$field])){
								$data[$field][] = $newfilename;
							}	else {
								$data[$field] = $newfilename;		
							}
						} else {
							$data[$field] = $newfilename;	
						}
					} else {
						// TODO: Handle file upload errors.
					}	
				}	
			}
		} else {
			// TODO: Error out 
		}
		
		// Tidy up array
		foreach($data[$field] as $key => $value){
			if(strlen(trim($value)) == 0){
				unset($data[$field][$key]);
			}
		}	
		
		return $data;
	}
	
}

?>