<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Product Management")
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */

#[Route('/api', name: 'app_product_')]
class ProductController extends AbstractController
{
    private $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Create a new product (Admin only)
     * 
     * @OA\RequestBody(
     *     description="Product data",
     *     required=true,
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(
     *             required={"name", "price"},
     *             @OA\Property(property="name", type="string", example="Premium Headphones"),
     *             @OA\Property(property="price", type="number", format="float", example=199.99),
     *             @OA\Property(property="description", type="string", example="High-quality noise-cancelling headphones"),
     *             @OA\Property(property="image", type="string", example="headphones.jpg"),
     *             @OA\Property(property="category", type="string", example="Electronics"),
     *             @OA\Property(property="quantity", type="integer", example=50),
     *             @OA\Property(property="inventoryStatus", type="string", enum={"INSTOCK", "LOWSTOCK", "OUTOFSTOCK"}, example="INSTOCK")
     *         )
     *     )
     * )
     * @OA\Response(
     *     response=201,
     *     description="Product created successfully",
     *     @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Product created successfully")
     *     )
     * )
     * @OA\Response(
     *     response=400,
     *     description="Validation error",
     *     @OA\JsonContent(
     *         @OA\Property(property="error", type="string", example="Missing required fields")
     *     )
     * )
     * @OA\Response(
     *     response=403,
     *     description="Forbidden",
     *     @OA\JsonContent(
     *         @OA\Property(property="error", type="string", example="Forbidden"),
     *         @OA\Property(property="message", type="string", example="You need administrator privileges to add products")
     *     )
     * )
     * @OA\SecurityRequirement(name="bearerAuth")
     */
    #[Route('/product', name: 'create_product', methods: 'post')]
    public function createProduct(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if ($user->getEmail() !== 'admin@admin.com') {
            return new JsonResponse(
                ['error' => 'Forbidden',
                'message' => 'You need administrator privileges to add products'], 
                Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['name'], $data['price'])) {
            return new JsonResponse(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['code']) && $this->productRepository->findOneBy(['code' => $data['code']])) {
            return new JsonResponse(['error' => 'Product code already exists'], Response::HTTP_CONFLICT);
        }

        if (isset($data['name']) && $this->productRepository->findOneBy(['name' => $data['name']])) {
            return new JsonResponse(['error' => 'Product name already exists'], Response::HTTP_CONFLICT);
        }

        if (isset($data['name']) && !is_string($data['name'])) {
            return new JsonResponse(['error' => 'Must be a string'], Response::HTTP_BAD_REQUEST);
        }
        
        if (isset($data['price']) && !is_numeric($data['price'])) {
            return new JsonResponse(['error' => 'Must be a number'], Response::HTTP_BAD_REQUEST);
        }
        
        if (isset($data['quantity']) && !is_numeric($data['quantity'])) {
            return new JsonResponse(['error' => 'Must be a number'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['inventoryStatus'])) {
            $validStatuses = ['INSTOCK', 'LOWSTOCK', 'OUTOFSTOCK'];
            if (!in_array($data['inventoryStatus'], $validStatuses)) {
                return new JsonResponse(['error' => "inventoryStatus Must be one of: " . implode(', ', $validStatuses)], Response::HTTP_BAD_REQUEST);
            }
        }


        $product = new Product();
        $product->setName($data['name']);
        $product->setPrice($data['price']);

        if (isset($data['code'])) {
            $product->setCode($data['code']);
        }
        if (isset($data['description'])) {
            $product->setDescription($data['description']);
        }
        if (isset($data['image'])) {
            $product->setImage($data['image']);
        }
        if (isset($data['category'])) {
            $product->setCategory($data['category']);
        }
        if (isset($data['internalReference'])) {
            $product->setInternalReference($data['internalReference']);
        }
        if (isset($data['shellId'])) {
            $product->setShellId($data['shellId']);
        }
        if (isset($data['inventoryStatus'])) {
            $product->setInventoryStatus($data['inventoryStatus']);
        }
        if (isset($data['rating'])) {
            $product->setRating($data['rating']);
        } 
        if (isset($data['quantity'])) {
            $product->setQuantity($data['quantity']);
        }  

        $product->setCreatedAt(new \DateTime());
        $product->setUpdatedAt(new \DateTime());

        $em->persist($product);
        $em->flush();

        return new JsonResponse(['message' => 'Product created successfully'], Response::HTTP_CREATED);
    }


    /**
     * List all products
     * 
     * @OA\Response(
     *     response=200,
     *     description="Returns list of all products",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Premium Headphones"),
     *             @OA\Property(property="description", type="string", example="High-quality noise cancelling headphones"),
     *             @OA\Property(property="price", type="number", format="float", example=199.99),
     *             @OA\Property(property="image", type="string", example="headphones.jpg"),
     *             @OA\Property(property="category", type="string", example="Electronics"),
     *             @OA\Property(property="quantity", type="integer", example=50),
     *             @OA\Property(property="inventoryStatus", type="string", enum={"INSTOCK", "LOWSTOCK", "OUTOFSTOCK"}, example="INSTOCK"),
     *             @OA\Property(property="rating", type="number", format="float", example=4.5)
     *         )
     *     )
     * )
     * @OA\SecurityRequirement(name="bearerAuth")
     */
    #[Route('/product', name: 'list_products', methods: 'get')]
    public function getProducts(SerializerInterface $serializer): Response
    {
        $products = $this->productRepository->findAll();

        return new JsonResponse(
            $serializer->serialize($products, 'json', [
                'groups' => ['product:read'],
                'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
            ]),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * Update a product (Admin only)
     * 
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="ID of product to update",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\RequestBody(
     *     description="Product data to update",
     *     required=true,
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="Updated Product Name"),
     *             @OA\Property(property="price", type="number", format="float", example=249.99),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="quantity", type="integer", example=30),
     *             @OA\Property(property="inventoryStatus", type="string", enum={"INSTOCK", "LOWSTOCK", "OUTOFSTOCK"}, example="LOWSTOCK")
     *         )
     *     )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Product updated successfully",
     *     @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Product updated successfully")
     *     )
     * )
     * @OA\Response(
     *     response=400,
     *     description="Validation error",
     *     @OA\JsonContent(
     *         @OA\Property(property="error", type="string", example="Validation failed"),
     *         @OA\Property(property="errors", type="object",
     *             @OA\Property(property="name", type="string", example="Must be a string"),
     *             @OA\Property(property="price", type="string", example="Must be a number")
     *         )
     *     )
     * )
     * @OA\Response(
     *     response=403,
     *     description="Forbidden - Admin only",
     *     @OA\JsonContent(
     *         @OA\Property(property="error", type="string", example="Forbidden"),
     *         @OA\Property(property="message", type="string", example="You need administrator privileges to update products")
     *     )
     * )
     * @OA\Response(
     *     response=404,
     *     description="Product not found",
     *     @OA\JsonContent(
     *         @OA\Property(property="error", type="string", example="Product not found")
     *     )
     * )
     * @OA\SecurityRequirement(name="bearerAuth")
     */
    #[Route('/product/{id}', name: 'update_product', methods: 'patch')]
    public function updateProduct(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if ($user->getEmail() !== 'admin@admin.com') {
            return new JsonResponse(
                ['error' => 'Forbidden',
                'message' => 'You need administrator privileges to update products'], 
                Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON data'], 400);
        }

        $product = $this->productRepository->find($id);
        $errors = [];

        if (!$product) {
            return new JsonResponse(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }
        
        if (isset($data['name']) && !is_string($data['name'])) {
            $errors['name'] = 'Must be a string';
        }
        
        if (isset($data['price']) && !is_numeric($data['price'])) {
            $errors['price'] = 'Must be a number';
        }
        
        if (isset($data['quantity']) && !is_numeric($data['quantity'])) {
            $errors['quantity'] = 'Must be a number';
        }

        if (isset($data['inventoryStatus'])) {
            $validStatuses = ['INSTOCK', 'LOWSTOCK', 'OUTOFSTOCK'];
            if (!in_array($data['inventoryStatus'], $validStatuses)) {
                $errors['inventoryStatus'] = 'Must be one of: ' . implode(', ', $validStatuses);
            }
        }

        if (!empty($errors)) {
            return $this->json([
                'error' => 'Validation failed',
                'errors' => $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        $allowedFields = [
            'name', 'code', 'description', 'image', 'category',
            'price', 'quantity', 'internalReference', 'shellId',
            'inventoryStatus', 'rating'
        ];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $setter = 'set' . ucfirst($field);
                if (method_exists($product, $setter)) {
                    $product->$setter($data[$field]);
                }
            }
        }
        
        $product->setUpdatedAt(new \DateTime());
        $em->persist($product);
        $em->flush();

        return new JsonResponse(['message' => 'Product updated successfully'], Response::HTTP_OK);
    }

    /**
     * Delete a product (Admin only)
     * 
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="ID of product to delete",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\Response(
     *     response=200,
     *     description="Product deleted successfully",
     *     @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Product deleted")
     *     )
     * )
     * @OA\Response(
     *     response=403,
     *     description="Forbidden - Admin only",
     *     @OA\JsonContent(
     *         @OA\Property(property="error", type="string", example="Forbidden"),
     *         @OA\Property(property="message", type="string", example="You need administrator privileges to delete products")
     *     )
     * )
     * @OA\Response(
     *     response=404,
     *     description="Product not found",
     *     @OA\JsonContent(
     *         @OA\Property(property="error", type="string", example="Product not found")
     *     )
     * )
     * @OA\SecurityRequirement(name="bearerAuth")
     */
    #[Route('/product/{id}', name: 'delete_product', methods: 'delete')]
    public function deleteProduct(int $id, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if ($user->getEmail() !== 'admin@admin.com') {
            return new JsonResponse(
                ['error' => 'Forbidden',
                'message' => 'You need administrator privileges to update products'], 
                Response::HTTP_FORBIDDEN);
        }

        $product = $this->productRepository->find($id);

        if (!$product) {
            return new JsonResponse(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($product);
        $em->flush();

        return new JsonResponse(['message' => 'Product deleted'], Response::HTTP_OK);
    }


}
