<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\UserWishlist;
use App\Entity\WishlistItem;
use App\Repository\ProductRepository;
use App\Repository\WishlistItemRepository;
use App\Service\SerializeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use OpenApi\Annotations\Server;
use OpenApi\Serializer;

/**
 * @OA\Tag(name="Wishlist Management")
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
#[Route('/api/wishlist', name: 'app_wishlist_')]
class WishlistController extends AbstractController
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
     * Get user's wishlist
     * 
     * @OA\Response(
     *     response=200,
     *     description="Returns wishlist items",
     *     @OA\JsonContent(
     *         @OA\Property(property="items", type="array", @OA\Items(ref="#/components/schemas/WishlistItem"))
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
    #[Route('', name: 'get_wishlist', methods: ['GET'])]
    public function getWishlist(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $wishlist = $user->getWishlist();
        if (!$wishlist) {
            return $this->json(['items' => []]);
        }

        $items = [];
        foreach ($wishlist->getItems() as $item) {
            $items[] = [
                'id' => $item->getId(),
                'product' => $this->serializeService->serializeProduct($item->getProduct()),
                'addedAt' => $item->getAddedAt()->format('Y-m-d H:i:s')
            ];
        }

        return $this->json(['items' => $items]);
    }

    /**
     * Add product to wishlist
     * 
     * @OA\Parameter(
     *     name="productId",
     *     in="path",
     *     description="ID of product to add to wishlist",
     *     required=true,
     *     @OA\Schema(type="integer", example=1)
     * )
     * @OA\Response(
     *     response=201,
     *     description="Product added to wishlist",
     *     @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Product added to wishlist successfully")
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
     *     description="Product not found",
     *     @OA\JsonContent(
     *         @OA\Property(property="error", type="string", example="Product not found")
     *     )
     * )
     * @OA\SecurityRequirement(name="bearerAuth")
     */
    #[Route('/add/{productId}', name: 'add_item', methods: ['POST'])]
    public function addToWishlist(int $productId, ProductRepository $productRepo): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $product = $productRepo->find($productId);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $wishlist = $user->getWishlist();
        if (!$wishlist) {
            $wishlist = new UserWishlist();
            $wishlist->setUser($user);
            $this->em->persist($wishlist);
        }

        $exists = false;
        foreach ($wishlist->getItems() as $item) {
            if ($item->getProduct()->getId() === $product->getId()) {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            $wishlistItem = new WishlistItem();
            $wishlistItem->setWishlist($wishlist);
            $wishlistItem->setProduct($product);
            $this->em->persist($wishlistItem);
            $this->em->flush();
        }else{
            return $this->json(['message' => 'Product already in wishlist'], Response::HTTP_OK);
        }

        return $this->json(['message' => 'Product added to wishlist successfully'], Response::HTTP_CREATED);
    }

    /**
     * Remove item from wishlist
     * 
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="ID of wishlist item to remove",
     *     required=true,
     *     @OA\Schema(type="integer", example=1)
     * )
     * @OA\Response(
     *     response=200,
     *     description="Item removed successfully",
     *     @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Item removed from wishlist successfully")
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
     *         @OA\Property(property="error", type="string", example="Wishlist item not found")
     *     )
     * )
     * @OA\SecurityRequirement(name="bearerAuth")
     */
    #[Route('/remove/{id}', name: 'remove_item', methods: ['DELETE'])]
    public function removeFromWishlist(int $id, WishlistItemRepository $wishlistItemRepo): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $wishlistItem = $wishlistItemRepo->find($id);
        if (!$wishlistItem || $wishlistItem->getWishlist()->getUser() !== $user) {
            return $this->json(['error' => 'Wishlist item not found'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($wishlistItem);
        $this->em->flush();

        return $this->json(['message' => "Item removed from wishlist successfully"], Response::HTTP_OK);
    }


    /**
     * Clear all items from wishlist
     * 
     * @OA\Response(
     *     response=200,
     *     description="Wishlist cleared successfully",
     *     @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Wishlist cleared successfully")
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
    #[Route('/clear', name: 'clear_wishlist', methods: ['DELETE'])]
    public function clearWishlist(): JsonResponse{
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $wishlist = $user->getWishlist();
        if ($wishlist) {
            foreach ($wishlist->getItems() as $item) {
                $this->em->remove($item);
            }
            $this->em->flush();
        }

        return $this->json(['message' => 'Wishlist cleared successfully'], Response::HTTP_OK);
    }
}