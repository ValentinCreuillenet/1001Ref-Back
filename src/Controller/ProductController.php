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
     * @Route("/product/create", name="createproduct", methods={"POST"})
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
     * @Route("/api/products", name="geteveryproducts", methods={"GET"})
     */
    public function getProducts(): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $products = $entityManager->getRepository(Product::class)->findAll();
        $result = $this->getSerializer()->serialize($products, 'json');
        $response = new Response(   $result,
        Response::HTTP_OK,
        ['Access-Control-Allow-Origin' => '*']);
        return $response;
    }



    /**
     * @Route("/product/{id}", name="getproduct", methods={"GET"})
     */
    public  function getProduct(int $id): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        $product = $entityManager->getRepository(Product::class)->find($id);
        
        $this->dehydration($product);
        

        $result = $this->getSerializer()->serialize($product, 'json');
        if ($product == null) {
           $response = new Response(
             $result,
             Response::HTTP_NOT_FOUND,
             ['Access-Control-Allow-Origin' => '*']
            );
        }else {
            $response = new Response(
                $result,
                Response::HTTP_OK,
                ['Access-Control-Allow-Origin' => '*']
               );
        }
      
            return $response;
           
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
     * @Route("/variation/delete/{id}", name="deleteVariation", methods={"DELETE"})
     */
    public function deleteVariationById(int $id): Response
    {
        // on fait appel au gestionnaire d'entité de doctrine
        $em = $this->getDoctrine()->getManager();

        // on récupère notre objet et on le pointe par son id
        $variation = $em->getRepository(Variation::class)->find($id);
        
        // on vérifie si le produit existe
        if($variation != null){
            var_dump($variation->getProduct()->getName());

            // on indique a Doctrine que l'on souhaite supprimer un produit
            $em->remove($variation);

            // applique le changement
            $em->flush();

            return new Response(
                "Le produits a bien été supprimé.",
                Response::HTTP_OK
            );
        } else {

            return new Response(
                "Ce produits n'existe pas.",
                Response::HTTP_NOT_FOUND
            );
        }

    }



    /**
     * @Route("/product/update/{id}", name="updateProduct", methods={"PUT"})
     */
    public function updateProduct(Request $request): Response
    {
        // decode the datas sent
        $form = $request->toArray();

        // initialization of an error code
        $response = new Response(
            "Conflit",
            Response::HTTP_CONFLICT
        );

        // fetching the object from Doctrine
        $em = $this->getDoctrine()->getManager();
        $product = $em->getRepository(Product::class)->find(['id' => $form['id']]);

        // if the product exist I update it
        if($product != null){
           
            // hydrate product
            $product->hydrate($form, $this->getDoctrine());

            // save in database
            $em->persist($product);
            $em->flush();

            // change the status code
            $response = new Response(
                'Produit mit à jour',
                Response::HTTP_OK,
                ['Access-Control-Allow-Origin' => '*']
            );

            // return confirmation
            return $response;

        }

        return $response = new Response(
            "Le produit n'existe pas",
            Response::HTTP_NOT_FOUND,
            ['Access-Control-Allow-Origin' => '*']
        );
       

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
     * returns an object that serializes the doctrine entities into a json array
     */
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



    /**
     * 
     */
    private function dehydration($product){
        foreach($product->getOwner()->getProducts() as $products){
            $products->getOwner()->getProducts()->removeElement($products);
        }
        
    }

}
