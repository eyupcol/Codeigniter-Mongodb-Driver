# Codeigniter mongodb driver

Codeigniter 3.x Mongodb 3.x Driver (Php5 / Php7)
## Setting
<ol>
<li>Copy Mdb.php file into application/libraries directory.</li>
<li>Add Mdb.php to&nbsp;$autoload['libraries'] = array(); (application/config/autoload.php)</li>
</ol>
## Usage:

### Insert

Insert single document (row):

    $this->mdb->insert('collection',$data=array()); // returns boolean (true/false)
    $last_id = $this->mdb->insert_id();
    
Examples:
    
    if($this->mdb->insert('fruits',array("name"=>"banana", "amount"=>50, "color"=>"yellow"))){
        echo "Inserted a new fruit with id: $this->mdb->insert_id()";
    }else{
        echo "Could not be inserted";
    }
    
Insert a stack:

Returns number of inserted documents.

    $data = array(
			array("name"=>"mango","amount"=>13,"color"=>"green"),
			array("name"=>"lemon","amount"=>7,"color"=>"yellow"),
			array("name"=>"melon","amount"=>2,"color"=>"yellow"),
			array("name"=>"mushroom","amount"=>12,"color"=>"brown"),
			array("name"=>"pear","amount"=>6,"color"=>"reddish"),
			array("name"=>"pineapple","amount"=>8,"color"=>"darkgreen"),
			array("name"=>"plum","amount"=>58,"color"=>"red"),
			array("name"=>"plum","amount"=>7,"color"=>"green"),
			array("name"=>"fig","amount"=>76,"color"=>"brown")
		);
    echo $this->mdb->insert_batch("fruits",$data) . " fruits inserted."; // 9 fruits inserted.
    
    
### Delete

Delete documents:

Returns number of deleted documents (rows).

	echo $this->mdb->delete("fruits",array('name'=>'plum')) . ' fruits deleted.'; // 2 fruits deleted.
    
