<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;


use App\Entity\Product;
use App\Entity\Variation;
use App\Entity\Tags;
use App\Entity\User;




class ProductController extends AbstractController
{
    /**
     * @Route("/product/create", name="product", methods={"POST"})
     */
    public function createProduct(Request $request): Response
    {
        $product = new Product();
        $product->hydrate($request->toArray(), $this->getDoctrine());

        $entityManager = $this->getDoctrine()->getManager();

        $entityManager->persist($product);
        $entityManager->flush();

        return new Response('Saved new product with id '.$product->getId());

    }


    /**
     * @Route("/getProducts/{id}", name="getProducts", methods={"GET"})
     */
    public function getProductsByUserId(int $id): Response
    {
        // on récupère un utilisateur par son id
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);

        // on vérifie si l'utilisateur existe
        if($user ==  null){
            return new Response(
                "L'utilisateur n'existe pas.",
                response::HTTP_NOT_FOUND
            );
        }

        // on récupère tous les produits de l'utilisateur courant
        $products = $user->getProducts();
        $this->dehydrate($products);

        // si l'utilisateur existe mais qu'il n'y a pas de produits
        if ($products->isEmpty()) {
            return new Response(
                "L'utilisateur courant n'a pas de produits.",
                Response::HTTP_NO_CONTENT
            );
        } else {
            $products = $this->getSerializer()->serialize($products, 'json');
            return new Response(
                $products,
                Response::HTTP_OK
            );
        }

    }

    /**
     * alleviate the datas sent to the front by setting products properties to null or an empty string
     */
    private function dehydrate($products){
        foreach ($products as $product){
            $product->setCategory("");
            $product->setDescription("");
            // $product->clearTag();
            $product->setOwner(null);
        }
        
        
    }


    /**
     * delete a product from database by its id
     * @param int $id
     */
    private function deleteProductById($id){
        
    }

    private function getSerializer(){
        $encoder = new JsonEncoder();
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
            return $object->getId();
            },
        ];
        $normalizer = new ObjectNormalizer(null, null, null, null, null, null, $defaultContext);
        return new Serializer([$normalizer], [$encoder]);
    }

}
