<?php


class RecipeService{
    
    private $conn;
    
    private $countOfItems = null;
    
    
    public function __construct($conn) {
       
        if(!$conn instanceof Database){
            throw new Exception("Vyskytol sa problém s databázou.");
        }
        $this->conn = $conn;
    }
    
    public function recieveRecipies($pageNumber, $peerPage, $searchQuery = null){
        
        $offset = ($pageNumber == 1 ? 0 :  ($pageNumber * $peerPage) - $peerPage);
      
        
        return  $this->conn->select("SELECT p.`id`, p.`code`, p.`label`, p.`price`, SUM(i.`quantity_kg` * c.`price`) as total_price
                                    FROM `product` p
                                    LEFT JOIN `product_item` i ON p.`id`=i.`id_product`
                                    LEFT JOIN `color` c ON i.`id_color`=c.`id`".
                                    $this->where($searchQuery)."
                                    GROUP BY p.`id`
                                    LIMIT $offset,  $peerPage");
    }
    
    
    public function getRecipeById($recipeId){
        return $this->conn->select("SELECT p.`id`, p.`code`, p.`label`, p.`create`, p.`price`,p.`recipe`, SUM(i.`quantity_kg` * c.`price`)  as total_price
                                    FROM `product` p
                                    LEFT JOIN `product_item` i ON p.`id`=i.`id_product`
                                    LEFT JOIN `color` c ON i.`id_color`=c.`id`
                                    WHERE p.`id`=?
                                    group by p.`id` LIMIT 1", array( $recipeId ));
    }
    
        
    
    public function create($code, $label, $price, $isRecipe){
            $this->validateRecipe($code, $label, $price);
            $this->conn->insert("INSERT INTO `product` (`code`, `label`, `price` , `recipe`) VALUES (?,?,?,?)", 
            array($code, $label, $price, $isRecipe));
    }
    
    public function update($code, $label, $price, $recipeId){
            $this->validateRecipe($code, $label, $price);
            $this->conn->insert("UPDATE `product` SET `code`=?, `label`=?, `price`=? WHERE `id`=? LIMIT 1", 
            array($code, $label, $price, $recipeId));
    }
    
    public function delete($recipeId){
            $this->conn->insert("DELETE FROM `product` WHERE `id`=? LIMIT 1" , array( $recipeId ));
            $this->conn->insert("DELETE FROM `product_item` WHERE `id_product`=?" , array( $recipeId ));
    }


    public function getCountOfAllProducts($q = null){
        if($this->countOfItems == null){
            $count =  $this->conn->select("SELECT count(*) FROM `product` p".$this->where($q));
            $this->countOfItems = $count[0]["count(*)"];
        }
        return (int)$this->countOfItems;
    }
    
   

    private function validateRecipe($code, $label, $price){
        
        if(strlen($code) > 10){
            throw new ValidationException("Kód môže mať max. 10 znakov");
        }
        
        if(strlen($label) > 255){
            throw new ValidationException("Nazov môže mať max. 255 znakov");
        }
        
        if(!Validator::isFloat($price, 4)){
            throw new ValidationException("Cena obsahuje neplatnú hodnotu.");
        }
        
    }
    
    
    public function getInsertId(){
        return $this->conn->getInsertId();
    }
    
    private function where($searchQuery){
        if($searchQuery != null)   return " WHERE p.`label` LIKE '%".$searchQuery."%' OR p.`code` LIKE '%".$searchQuery."%'";
    }
}

?>