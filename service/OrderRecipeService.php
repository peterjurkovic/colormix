
<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RecipeItemService
 *
 * @author Peto
 */
class OrderRecipeService {
    
    private $conn;
    private $itemId;
    public function __construct($conn) {
       
        if(!$conn instanceof Database){
            throw new Exception("Vyskytol sa problém s databázou.");
        }
        $this->conn = $conn;
    }
    
    public function getRecipeInfo($idRecipe){
       return  $this->conn->select("SELECT p.`id`, p.`code`, p.`label`, i.`quantity`,i.`id_order`,i.`price_sale`, i.`item_count`
                                    FROM `product` p, `order_item` i
                                    WHERE i.`id_product`=p.`id` AND i.`id`=? LIMIT 1", 
                array( $idRecipe ));
    }

    public function create($idColor, $price, $quantityKg, $orderId, $itemId){
        $this->validateRecipeItem($idColor, $price, $quantityKg, $itemId);
        $this->conn->insert("INSERT INTO `order_subitem` (`id_color`, `price`, `quantity_kg`, `id_order_item`) VALUES (?,?,?,?)", 
                array( $idColor, $price, $quantityKg, $itemId));
    }
    
    public function updateItem($itemId, $quantity, $priceSale){
         $this->checkQuantity($quantity);
         $this->conn->update("UPDATE `order_item` SET `quantity`=?, `price_sale`=? WHERE `id`=? LIMIT 1", 
                 array($quantity, floatval($priceSale), $itemId));
    }
    
    
    public function updateItemPriceAndQunatity($itemId, $quantity, $price){
        
         $this->checkQuantity($quantity);
         $this->checkQuantity($price);
         $this->conn->update("UPDATE `order_subitem` SET `quantity_kg`=?, `price`=? WHERE `id`=? LIMIT 1", 
                 array($quantity, $price, $itemId));
    }
    
    
    
    
    public function delete($itemId){
        $this->conn->delete("DELETE FROM `order_subitem` WHERE `id`=?", array( $itemId ));
    }


    public function getRecipeItemsBy($itemId, $orderId){
        return $this->conn->select(
            "SELECT i.`id`, oi.`id` as id2,  c.`name`, c.`code`,c.`color_type`, i.`price`,
                oi.`price_sale`, i.`quantity_kg`, m.`unit`, m.`id` as id_unit, oi.`quantity`
            FROM  `order_subitem` i 
            JOIN `color` c ON i.`id_color`=c.`id`
            JOIN `measurement` m ON m.`id`=c.`id_measurement`
            JOIN `order_item` oi ON oi.id = i.id_order_item
            WHERE i.`id_order_item`= ? ", 
                array( $itemId ));
    }
    
    
    
     private function validateRecipeItem($idColor, $price, $quantityKg, $itemId){
        if($idColor == 0)
            throw new ValidationException("Nie je vybratá položka materiálu.");
 
        $this->checkQuantity($quantityKg);
        
        $r =  $this->conn->select("SELECT count(*) FROM `order_subitem` WHERE  id_color=? AND id_order_item=?", 
                array( $idColor, $itemId ));
        
        if($r[0]["count(*)"] > 0)
            throw new ValidationException("Položka sa už v receptúre nachádza.");
        
        if(! Validator::isFloat($price, 5)){
            throw new ValidationException("Cena za jednotku obsahuje neplatnú hodnotu.");
        }
        
    }
    
    public function checkQuantity($quantityKg){
         if(!Validator::isFloat($quantityKg, 5))
            throw new ValidationException("Dávka na 1 kg obsahuje neplatnú hodnotu.");
        
    }
    
    public function isUsed($quantityKg){
         if(!Validator::isFloat($quantityKg, 5))
            throw new ValidationException("Dávka na 1 kg obsahuje neplatnú hodnotu.");
        
    }
}

?>
