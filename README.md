# Codeigniter mongodb driver - 2016

Codeigniter 3.x Mongodb 3.x Driver (Php5 / Php7)
## Setting
<ol>
<li>Copy Mdb.php file into application/libraries directory and mongo.php file into application/config.</li>
<li>Set your database preferences in the mongo.php</li>
<li>Add Mdb.php to&nbsp;$autoload['libraries'] = array(); (application/config/autoload.php)</li>
</ol>
## Usage:

### Switch between databases
It will be created if does not exists, otherwise switched to another database
    
    $this->mdb->select_db($db);
	

### Insert

##### Insert single document (row):

    $this->mdb->insert('collection',$data=array()); // returns boolean (true/false)
    $last_id = $this->mdb->insert_id();
    Examples:
    if($this->mdb->insert('fruits',array("name"=>"banana", "amount"=>50, "color"=>"yellow"))){
        echo "Inserted a new fruit with id: $this->mdb->insert_id()";
    }else{
        echo "Could not be inserted";
    }
    
##### Insert a data set:

     // $this->mdb->insert_batch("fruits",$data) // returns the number of documents inserted.
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
   
### Update

	// $this->mdb->update("collection",$data,$filter,$options=array('multi'=>true,'upsert'=>false)); // returns the number of documents updated.
		
	$data = array("amount"=>17,"color"=>"yellow","price"=>20.15);

	echo $this->mdb->update("fruits",$data,array("name"=>"lemon")) . " documents updated.";
    
### Delete

	// $this->mdb->delete("collection",array("_id"=>$id),$limit=0) // returns the number of documents(rows) deleted
	echo $this->mdb->delete("fruits",array('name'=>'plum')) . ' fruits deleted.'; // 2 fruits deleted.
	echo $this->mdb->delete("fruits",array('name'=>'plum'),1) . ' fruits deleted.'; // 1 fruits deleted. // this will delete only 1 document/row.

Delete all documents in a collection (empty table):

	$this->mdb->delete("collection"); // to delete all fruits: $this->mdb->delete("fruits") this will delete all fruits..

### Reading

##### Fetching all records from a collection:

	$this->mdb->get('collection'); // returns all results
	
	$fruits = $this->mdb->get('fruits');
	
	echo "The number of fruits " . $fruits->num_rows(). '<br>';
	
	foreach ($fruits->result() as $fruit) {
            echo $fruit->name .'<br>';
        }
        
        // result:
        The number of fruits 9
        mango
        lemon
        melon
        mushroom
        pear
        pineapple
        plum
        plum
        fig
        
##### The number of results:

	$this->mdb->get('collection')->num_rows(); // returns interger results number
	
##### Where:

	$this->mdb->where(array("color"=>"green"));
	$results = $this->mdb->get('fruits')->results();
	
	OR
	$results = $this->mdb->where(array("color"=>"green"))->get('fruits')->results();
	
	Greater than:
	$this->mdb->where(array("color"=>"green","amount"=>['$gt'=>5])); // this retrieves only fruits green and its amount value greater than five.
	
	
##### Limit

limit($limit,$offset=0) $limit: the number of results will be shown, $offset: the number of documents to skip before returning

	$this->mdb->limit(10);
	$this->mdb->limit(10,20);
	$this->mdb->limit(10,20)->get('collection');

##### Order By

	$this->mdb->order_by(array('field'=>'sorttype')); // sorting type: descending sort = 1, ascending sort = -1
	$this->mdb->order_by(array('name'=>1));
	$this->mdb->order_by(array('id'=>-1, 'price'=>-1));
	
##### Select
If you want to select some fields from your collection, you can use select() function.
	
	$this->mdb->select($fields);
	$this->mdb->select(array('name','price','amount'));
	

##### Mixed Examples:

	$this->mdb->select('*');
	$this->mdb->limit(10);
	$this->mdb->skip(20);
	$this->mdb->where(array('$or'=>array(['color'=>'green'],['color'=>'red']),'name'=>'mango','amount'=>['$lt'=>5]));
	$this->mdb->order_by(array('amount'=>-1));
	$results = $this->mdb->get('fruits');
	$num_rows = $results->num_rows();
	$fruits = $results->result();
	
	OR
	
	$fruits = $this->mdb->select(['name','color'])->limit(10,20)->where(['color'=>'yellow'])->order_by(['price'=>-1])->get('fruits')->result();
	
	foreach($fruits as $fruit)
	{
		echo 'name: '. $fruit->name . ' color: '. $fruit->color . '<br>';
	}
	
	
	

	
	
	

