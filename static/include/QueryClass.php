<?php

include_once 'include/jwt.php';
include_once 'include/utils.php';

class QueryClass {

    // DB stuff
    private $conn;
    private $table;

    // Post Properties
    private $code;
    private $content;
    private $token;
    private $parsed_token;    

    // Constructor with DB
    public function __construct($pdo) {
        $this->conn = $pdo;
    }

    public function get_code(){
        return $this->code;
    }

    public function get_content(){
        return $this->content;
    }

    // Vérifier et parser la requête
    public function parse_request($data) {

        if(sizeof($data) != 3){
            throw new Exception("Invalid request format", 400);
        }
        else{
            if( ! array_key_exists("code", $data) || ! array_key_exists("token", $data)
                    || ! array_key_exists("content", $data)){
                throw new Exception("Invalid request format", 400);
            }
            else{
                if(gettype($data['code']) != 'integer' || ! in_array($data['code'], array(1, 3, 5, 7, 9, 11))){
                    throw new Exception("Invalid request code " . $data['code'], 400);
                }
                else if(gettype($data['token']) != 'string'){
                    throw new Exception("Invalid token format", 400);

                }
                else if(gettype($data['content']) != 'array'){
                    throw new Exception("Invalid content format", 400);
                }
            }
        }

        $this->code = $data['code'];
        $this->token = $data['token'];
        $this->content = $data['content'];
    }

    public function verify_token(){

        try{
            $this->parsed_token = parse_token($this->token);
        }
        catch(Exception $e){
            throw new Exception('Invalid Token: ' . $e->getMessage(), 401);
        }
    }

    public function createArticle(){

        $name = htmlspecialchars(strip_tags($this->content['name']));
        $product = htmlspecialchars(strip_tags($this->content['product']));
        $description = htmlspecialchars(strip_tags($this->content['description']));

        $query = 'INSERT INTO article(nom, code_produit, description) VALUES(:name, :product, :description);';

        // Prepare statement
        if( ($stmt = $this->conn->prepare($query)) === false ){
            throw new Exception("Can't create article: Query preparation failed", 406);
        }

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':product', $product);
        $stmt->bindParam(':description', $description);

        // Execute query
        if( ! $stmt->execute()){
            throw new Exception("Can't create article: " . $stmt->error, 406);
        }

        return array("code" => 0, "content" => array("success"=>1 , "message"=>""));
    }

    // Demande des noms d'entrepôts
    public function getWarehouses() {

        $type = htmlspecialchars(strip_tags($this->content['type']));
        $column = $type;

        if(!strcmp($type, "warehouse")){
            $type = "entrepot";
            $column = "nom";
        }

        // Create query
        $query = 'SELECT ' . $column . ' FROM ' . $type . ';';
        
        // Prepare statement
        if( ($stmt = $this->conn->prepare($query)) === false ){
        throw new Exception("Can't retrieve locations: Query preparation failed", 406);
        }

        // Execute query
        if( ! $stmt->execute()){
        throw new Exception("Can't retrieve locations: " . $stmt->error, 406);
        }

        $num = $stmt->rowCount();

        // Post array
        $list = array();
        
        if($num > 0){

            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // premier élément de array $row
                array_push($list, reset($row));
            }
        }

        $result = array("list" => $list);

        return array("code" => 2, "content" => $result);
    }


    // Demande d'information sur les produits
    public function getProducts() {

        $product = false;
        $warehouse = false;
        $allee = false;
        $travee = false;
        $niveau = false;
        $alveole = false;
        $where = false;
        $first = '';

        // Create query
        $query = 'SELECT code_produit as code, nom as product, quantity, entrepot as warehouse, allee, travee, niveau, alveole FROM joint_stock';


        // concaténations de clauses WHERE si nécessaire

        if( strcmp($this->content['product'], "*")  ){
            $query .= ' WHERE code_produit = :product';
            $product = true;
            $where = true;
        }

        $location = $this->content['location'];

        if( strcmp($location['warehouse'], "*") ){
            if($where)
                $first = ' AND ';
            else
                $first = ' WHERE ';
            $query .= $first . 'entrepot = :warehouse';
            $warehouse = true;
            $where = true;
        }

        if( strcmp($location['allee'], "*") ){
            if($where)
                $first = ' AND ';
            else
                $first = ' WHERE ';
            $query .= $first . 'allee = :allee';
            $allee = true;
            $where = true;
        }

        if( strcmp($location['travee'], "*")  ){
            if($where)
                $first = ' AND ';
            else
                $first = ' WHERE ';
            $query .= $first . 'travee = :travee';
            $travee = true;
            $where = true;
        }

        if( strcmp($location['niveau'], "*") ){
            if($where)
                $first = ' AND ';
            else
                $first = ' WHERE ';
            $query .= $first . 'niveau = :niveau';
            $niveau = true;
            $where = true;
        }

        if( strcmp($location['alveole'], "*")){
            if($where)
                $first = ' AND ';
            else
                $first = ' WHERE ';
            $query .= $first . 'alveole = :alveole';
            $alveole = true;
        }

        $query .= ';';
        
        // Prepare statement
        if( ($stmt = $this->conn->prepare($query)) === false ){
            throw new Exception("Can't retrieve Products: Query preparation failed", 406);
        } 

        // Clean & Bind data
        $location = $this->content['location'];
    
        if($product){
            $product = htmlspecialchars(strip_tags($this->content['product']));
            $stmt->bindParam(':product', $product);
        }
        if($warehouse){
            $warehouse = htmlspecialchars(strip_tags($location['warehouse']));
            $stmt->bindParam(':warehouse', $warehouse);
        }
        if($allee){
            $allee = htmlspecialchars(strip_tags($location['allee']));
            $stmt->bindParam(':allee', $allee);
        }
        if($travee){
            $travee = htmlspecialchars(strip_tags($location['travee']));
            $stmt->bindParam(':travee', $travee);
        }
        if($niveau){
            $niveau = htmlspecialchars(strip_tags($location['niveau']));
            $stmt->bindParam(':niveau', $niveau);
        }
        if($alveole){
            $alveole = htmlspecialchars(strip_tags($location['alveole']));
            $stmt->bindParam(':alveole', $alveole);
        }
            
        // Execute query
        if(!$stmt->execute()){
            throw new Exception("Can't retrieve Products: " . $stmt->error, 406);
        }

        $num = $stmt->rowCount();

        $products = array();

        if($num > 0){
            
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                extract($row);

                $location = array(
                    'warehouse' => $warehouse,
                    'allee' => $allee,
                    'travee' => $travee,
                    'niveau' => $niveau,
                    'alveole' => $alveole,
                );

                $product_item = array(
                    'code' => $code,
                    'name' => $product,
                    'quantity' => $quantity,
                    'location' => $location
                );

                // Push to "data"
                array_push($products, $product_item);
            }
        }
      
        $result = array('list' => $products);

        return array("code" => 4, "content" => $result);
    }   

    public function modify_quantity($product, $quantity, $warehouse, $allee, $travee, $niveau, $alveole){

        // on récupère quantity pour vérifier > 0 , et id_stock si on fait l'update après (éviter de refaire les mêmes jointures)
        $query_quantity = 'SELECT quantity AS old_quantity, id_stock FROM joint_stock
                            WHERE code_produit = :product
                                AND entrepot = :warehouse
                                AND allee = :allee
                                AND travee = :travee
                                AND niveau = :niveau
                                AND alveole = :alveole;';

        // Prepare statement
        $stmt_quantity =  $this->conn->prepare($query_quantity);
        if(!$stmt_quantity){
            throw new Exception("Can't update quantity: Query preparation failed", 406);
        }

        $stmt_quantity->bindParam(':product', $product);
        $stmt_quantity->bindParam(':warehouse', $warehouse);
        $stmt_quantity->bindParam(':allee', $allee);
        $stmt_quantity->bindParam(':travee', $travee);
        $stmt_quantity->bindParam(':niveau', $niveau);
        $stmt_quantity->bindParam(':alveole', $alveole);

        // Execute query
        if(!$stmt_quantity->execute()) {
            throw new Exception("Can't get current quantity: " . $stmt_quantity->error, 406);
        }

        if($stmt_quantity->rowCount() == 0){
            throw new Exception("Can't get current: Product does not exist", 406);
        }

        $row = $stmt_quantity->fetch(PDO::FETCH_ASSOC);
        // stock quantity dans $old_quantity et id_stock dans $id_stock
        extract($row);
        
        if($old_quantity + $quantity < 0){
            throw new Exception("Insufficient quantity in stock", 406);
        }
        else if($old_quantity + $quantity == 0){
            $query = 'DELETE FROM stock WHERE id_stock = ' . $id_stock . ';';
            // Prepare statement
            if( ($stmt = $this->conn->prepare($query)) === false){
                throw new Exception("Can't delete object from stock: Query preparation failed", 406);
            }
            // Execute query
            if(!$stmt->execute()) {
                throw new Exception("Can't delete object from stock: " . $stmt->error, 406);
            }
        }
        else{
            // Create query
            $query = 'UPDATE stock S                    

            SET quantity = quantity + ' . $quantity . ',
                id_stock = LAST_INSERT_ID(id_stock)

            WHERE id_stock = ' . $id_stock . ';';

            // Prepare statement
            if( ($stmt = $this->conn->prepare($query)) === false){
                throw new Exception("Query preparation failed", 406);
            }

            // Execute query
            if(!$stmt->execute()) {
                throw new Exception("Invalid request body: " . $stmt->error, 406);
            }
        }
        return $id_stock;
    }

    function add_object_to_stock($product, $quantity, $warehouse, $allee, $travee, $niveau, $alveole){

        if($quantity <= 0){
            throw new Exception("Product not in stock", 406);
        }

        $id_article = '(SELECT id_article FROM article WHERE code_produit = :product)';

        $id_site = '(SELECT id_site FROM joint_stock
                    WHERE entrepot = :warehouse 
                    AND allee = :allee 
                    AND travee = :travee 
                    AND niveau = :niveau
                    AND alveole = :alveole)';

        $query = 'INSERT INTO stock(quantity, id_article, id_site) VALUES(' . $quantity . ',' . $id_article . ',' . $id_site . ');';
                                                                                                                
        // Prepare statement
        if( ($stmt = $this->conn->prepare($query)) === false){
            throw new Exception("Query preparation failed", 406);
        }

        $stmt->bindParam(':product', $product);
        $stmt->bindParam(':warehouse', $warehouse);
        $stmt->bindParam(':allee', $allee);
        $stmt->bindParam(':travee', $travee);
        $stmt->bindParam(':niveau', $niveau);
        $stmt->bindParam(':alveole', $alveole);

        // Execute query
        if(!$stmt->execute()) {
            throw new Exception("Invalid request body: " . $stmt->error, 406);
        }
        
    }


    function object_exists($product, $warehouse, $allee, $travee, $niveau, $alveole){

        $query = 'SELECT * FROM joint_stock
                    WHERE entrepot = :warehouse
                    AND allee = :allee
                    AND travee = :travee
                    AND niveau = :niveau
                    AND alveole = :alveole
                    AND code_produit = :product;';


        // Prepare statement
        $stmt = $this->conn->prepare($query);
        if(!$stmt){
            throw new Exception("Query preparation failed", 406);
        }

        $stmt->bindParam(':warehouse', $warehouse);
        $stmt->bindParam(':allee', $allee);
        $stmt->bindParam(':travee', $travee);
        $stmt->bindParam(':niveau', $niveau);
        $stmt->bindParam(':alveole', $alveole);
        $stmt->bindParam(':product', $product);

        // Execute query
        if(!$stmt->execute()) {
            throw new Exception("Invalid request body: " . $stmt->error, 406);
        }

        if( ! $stmt->rowCount()){
            return false;
        }
        return true;
    }
    // Ajustement de stock
    public function update() {

        // Clean data
        $product = htmlspecialchars(strip_tags($this->content['product']));
        $quantity = htmlspecialchars(strip_tags($this->content['quantity']));
        $location = $this->content['location'];
        $warehouse = htmlspecialchars(strip_tags($location['warehouse']));
        $allee = htmlspecialchars(strip_tags($location['allee']));
        $travee = htmlspecialchars(strip_tags($location['travee']));
        $niveau = htmlspecialchars(strip_tags($location['niveau']));
        $alveole = htmlspecialchars(strip_tags($location['alveole']));


        // begin transaction
        $this->conn->begintransaction();

        try{
            // $id_stock = -1;

            // if( ! $this->object_exists($product, $warehouse, $allee, $travee, $niveau, $alveole)){
            //     $this->add_object_to_stock($product, $quantity, $warehouse, $allee, $travee, $niveau, $alveole);
            //     $id_stock = $this->conn->lastInsertId();
            // }
            // else{ 
            //     $id_stock = $this->modify_quantity($product, $quantity, $warehouse, $allee, $travee, $niveau, $alveole);
            // }

            $query = 'CALL AddToStock(@sid, @aid, :product, :warehouse, :allee, :travee, :niveau, :alveole, :quantity);';

            $stmt = $this->conn->prepare($query);

            if(!$stmt){
                throw new Exception("Query preparation failed", 406);
            }

            $stmt->bindParam(':product', $product);
            $stmt->bindParam(':warehouse', $warehouse);
            $stmt->bindParam(':allee', $allee);
            $stmt->bindParam(':travee', $travee);
            $stmt->bindParam(':niveau', $niveau);
            $stmt->bindParam(':alveole', $alveole);
            $stmt->bindParam(':quantity', $quantity);

            // Execute query
            try {
                if (!$stmt->execute()) {
                    throw new Exception("Call to AddStockFailed: " . $stmt->error, 406);
                }
            } catch (PDOException $e) {
                throw new Exception("Call to AddStockFailed: " . $e->getMessage(), 406);
            }

            $query = 'SELECT @sid, @aid;';

            $stmt = $this->conn->prepare($query);

            if(!$stmt){
                throw new Exception("Query preparation failed", 406);
            }
            // Execute query
            if(!$stmt->execute()) {
                throw new Exception("Call to AddStockFailed: " . $stmt->error, 406);
            }
            if($stmt->rowCount() == 0){
                throw new Exception("Call to AddStockFailed: Can't retrieve inserted id_stock", 406);
            }
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            

            $this->add_transaction($row['@sid'], $row['@aid'], $quantity);
            
            $this->conn->commit();
            return array("code" => 6, "content" => array("success" => 1, "message" => ""));
        }
        catch(Exception $e){
            $this->conn->rollback();
            throw $e;
        }
    }

    public function get_product_information(){

        $code = htmlspecialchars(strip_tags($this->content['code']));

        $query = 'SELECT * FROM article WHERE code_produit=:code';

        if( ($stmt = $this->conn->prepare($query)) === false){
            throw new Exception("Can't get product information: Query preparation failed", 406);
        }

        $stmt->bindParam(':code', $code);

        if( ! $stmt->execute()){
            throw new Exception("Can't get product information: " . $stmt->error, 406);
        }

        if( $stmt->rowCount() == 0){
            throw new Exception("Can't get product information: Product does not exist", 406);
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        extract($row);

        return array("code" => 10, "content" => array("id_article" => $id_article, "name" => $nom, "code" => $code_produit, "description" => $description));
    }

    public function create_space(){

        $space = htmlspecialchars(strip_tags($this->content['space']));
        $column = $space;
        $name = htmlspecialchars(strip_tags($this->content['name']));

        if( ! strcmp($space, "warehouse")){
            $space = "entrepot";
            $column = 'nom';
        }
            

        $query = 'INSERT INTO ' . $space . '(' . $column . ') VALUES(:name);';

        if( ($stmt = $this->conn->prepare($query)) === false){
            throw new Exception("Can't create space: Query preparation failed", 406);
        }

        $stmt->bindParam(':name', $name);

        if( ! $stmt->execute()){
            throw new Exception("Can't create space: " . $stmt->error, 406);
        }

        return array("code" => 8, "content" => array("success" => 1, "message" => ""));
    }

    public function add_transaction($id_site, $id_article, $delta){

        $date = date('Y-m-d h:i:s');
        $id_utilisateur = $this->parsed_token['uid'];
        $query = 'INSERT INTO transactions(id_utilisateur, id_article, id_site, delta, estampille) VALUES(' . $id_utilisateur . ', ' . $id_article . ', ' . $id_site . ', ' . $delta . ', "' . $date . '");';

        // Prepare statement
        if( ($stmt = $this->conn->prepare($query)) === false){
            throw new Exception("Save Transaction Failed: Query preparation failed", 406);
        }

        // Execute query
        if( ! $stmt->execute()) {
            throw new Exception("Save Transaction Failed: " . $stmt->error, 406);
        }
    }
}


?>