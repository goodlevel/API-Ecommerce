<?php

namespace App\Service;

use App\Entity\Product;

class SerializeService 
{
    /**
     * Serialize product data
     * 
     * @param Product $product
     * @return array
     */
    
     public function serializeProduct(Product $product): array
     {
         return [
             'id' => $product->getId(),
             'name' => $product->getName(),
             'price' => $product->getPrice(),
             'image' => $product->getImage(),
             'inventoryStatus' => $product->getInventoryStatus()
         ];
     }
}
