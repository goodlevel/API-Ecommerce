<?php

namespace App\Controller;

use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\UserCart;
use App\Repository\CartItemRepository;
use App\Repository\ProductRepository;
use App\Repository\UserCartRepository;
use App\Service\SerializeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Cart Management")
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
#[Route('/api/cart', name: 'app_cart_')]
class CartController extends AbstractController
{
    private Security $security;
    private EntityManagerInterface $em;
    private SerializeService $serializeService;

    public function __construct(Security $security, EntityManagerInterface $em, SerializeService $serializeService)
    {
        $this->security = $security;
        $this->em = $em;
        $this->serializeService = $serializeService;
    }

    /**
     * Get user's shopping cart
     * 
     * @OA\Response(
     *     response=200,
     *     description="Returns user's cart items",
     *     @OA\JsonContent(
     *         @OA\Property(property="items", type="array", @OA\Items(ref="#/components/schemas/CartItem")),
     *         @OA\Property(property="TotalItems", type="integer", example=3)
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Unauthorized",
     *     @OA\JsonContent(
     *         @OA\Property(property="error", type="string", example="Not authenticated")
     *     )
     * )
     * @OA\SecurityRequirement(name="bearerAuth")
     */
    #[Route('', name: 'get_cart', methods: ['GET'])]
    public function getCart(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $cart = $user->getCart();
        if (!$cart) {
            return $this->json(['items' => []]);
        }

        $items = [];
        foreach ($cart->getItems() as $item) {
            $items[] = [
                'id' => $item->getId(),
                'product' => $this->serializeService->serializeProduct($item->getProduct()),
                'quantity' => $item->getQuantity(),
                'addedAt' => $item->getAddedAt()->format('Y-m-d H:i:s'),
            ];
        }

        $items['TotalItems'] = count($cart->getItems());

        return $this->json(['items' => $items]);
    }

    /**
     * Add or Update item to cart
     * 
     * @OA\RequestBody(
     *     description="Cart item data",
     *     required=true,
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(
     *             required={"productId", "quantity"},
     *             @OA\Property(property="productId", type="integer", example=1, description="ID of the product to add"),
     *             @OA\Property(property="quantity", type="integer", example=1, description="Quantity to add (min: 1)")
     *         )
     *     )
     * )
     * @OA\Response(
     *     response=201,
     *     description="Item added to cart",
     *     @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Product added to cart successfully")
     *     )
     * )
     * @OA\Response(
     *     response=400,
     *     description="Bad request",
     *     @OA\JsonContent(
     *         @OA\Property(property="error", type="string", example="Missing productId or quantity")
     *     )
     * )
     * @OA\Response(
     *     response=404,
     *     description="Not found",
     *     @OA\JsonContent(
     *         @OA\Property(property="error", type="string", example="Product not found")
     *     )
     * )
     * @OA\SecurityRequirement(name="bearerAuth")
     */
    #[Route('/add', name: 'add_item', methods: ['POST'])]
    public function addToCart(Request $request, ProductRepository $productRepo): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['productId']) || !isset($data['quantity'])) {
            return $this->json(['error' => 'Missing productId or quantity'], Response::HTTP_BAD_REQUEST);
        }

        if ($data['quantity'] <= 0) {
            return $this->json(['error' => 'Invalid quantity'], Response::HTTP_BAD_REQUEST);
        }

        $product = $productRepo->find($data['productId']);
        
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        if ($product && $product->getInventoryStatus() === 'OUTOFSTOCK') {
            return $this->json(['error' => 'Product is out of stock'], Response::HTTP_BAD_REQUEST);
            
        }

        if ($product && $data['quantity'] > $product->getQuantity()) {
            return $this->json(['error' => 'Requested quantity exceeds available stock'], Response::HTTP_BAD_REQUEST);
        }


        $cart = $user->getCart();
        if (!$cart) {
            $cart = new UserCart();
            $cart->setUser($user);
            $this->em->persist($cart);
        }

        $existingItem = null;
        foreach ($cart->getItems() as $item) {
            if ($item->getProduct()->getId() === $product->getId()) {
                $existingItem = $item;
                break;
            }
        }

        if ($existingItem) {
            $existingItem->setQuantity($data['quantity']);
            $this->em->persist($existingItem);
            $this->em->flush();
            return $this->json(['message' => 'Cart Product Quantity updated successfully'], Response::HTTP_OK);
        } else {
            $cartItem = new CartItem();
            $cartItem->setCart($cart);
            $cartItem->setProduct($product);
            $cartItem->setQuantity($data['quantity']);
            $this->em->persist($cartItem);
        }

        $this->em->flush();

        return $this->json(['message' => 'Product added to cart successfully'], Response::HTTP_CREATED);
    }


    /**
     * Remove item from cart
     * 
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="ID of cart item to remove",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\Response(
     *     response=200,
     *     description="Item removed successfully",
     *     @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Item removed from cart successfully")
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Unauthorized",
     *     @OA\JsonContent(
     *         @OA\Property(property="error", type="string", example="Not authenticated")
     *     )
     * )
     * @OA\Response(
     *     response=404,
     *     description="Not found",
     *     @OA\JsonContent(
     *         @OA\Property(property="error", type="string", example="Cart item not found")
     *     )
     * )
     * @OA\SecurityRequirement(name="bearerAuth")
     */
    #[Route('/remove/{id}', name: 'remove_item', methods: ['DELETE'])]
    public function removeFromCart(int $id, CartItemRepository $cartItemRepo): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $cartItem = $cartItemRepo->find($id);
        if (!$cartItem || $cartItem->getCart()->getUser() !== $user) {
            return $this->json(['error' => 'Cart item not found'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($cartItem);
        $this->em->flush();

        return $this->json(['message' => "Item removed from cart successfully"], Response::HTTP_OK);
    }


     /**
     * Clear all items from cart
     * 
     * @OA\Response(
     *     response=200,
     *     description="Cart cleared successfully",
     *     @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Cart cleared successfully")
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Unauthorized",
     *     @OA\JsonContent(
     *         @OA\Property(property="error", type="string", example="Not authenticated")
     *     )
     * )
     * @OA\SecurityRequirement(name="bearerAuth")
     */
    #[Route('/clear', name: 'clear_cart', methods: ['DELETE'])]
    public function clearCart(UserCartRepository $cartRepo): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $cart = $user->getCart();
        if ($cart) {
            foreach ($cart->getItems() as $item) {
                $this->em->remove($item);
            }
            $this->em->flush();
        }

        return $this->json(['message' => "Cart cleared successfully"], Response::HTTP_OK);
    }

}