<?php

namespace App\Controller;

use App\Repository\ProductoRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CatalogController extends AbstractController
{
    // ── Catálogo principal ──────────────────────────────────────────────

    #[Route('/', name: 'catalog_index', methods: ['GET'])]
    public function index(Request $request, ProductoRepository $repo): Response
    {
        $query = $request->query->get('q', '');

        $productos = $query !== ''
            ? $repo->search($query)
            : $repo->findBy([], ['nombre' => 'ASC']);

        return $this->render('catalog/index.html.twig', [
            'productos' => $productos,
            'query' => $query,
        ]);
    }

    // ── Detalle de producto ────────────────────────────────────────────

    #[Route('/producto/{id}', name: 'catalog_show', methods: ['GET'])]
    public function show(int $id, ProductoRepository $repo): Response
    {
        $producto = $repo->find($id);

        if (!$producto) {
            throw $this->createNotFoundException('Producto no encontrado.');
        }

        return $this->render('catalog/show.html.twig', [
            'producto' => $producto,
        ]);
    }

    // ── Añadir al carrito ──────────────────────────────────────────────

    #[Route('/cart/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function addToCart(int $id, Request $request, CartService $cart, ProductoRepository $repo): Response
    {
        $qty = max(1, (int) $request->request->get('quantity', 1));
        $added = $cart->add($id, $qty);

        $producto = $repo->find($id);
        $nombre = $producto ? $producto->getNombre() : 'Producto';

        if ($added === 0) {
            $this->addFlash('danger', sprintf('No se puede añadir "%s": ya tienes el máximo en stock en el carrito.', $nombre));
        } elseif ($added < $qty) {
            $this->addFlash('warning', sprintf('Solo se han añadido %d uds. de "%s" (límite de stock alcanzado).', $added, $nombre));
        } else {
            $this->addFlash('success', sprintf('✓ %s × %d añadido al carrito.', $nombre, $added));
        }

        // Redirigir a la página de la que venía
        $referer = $request->headers->get('referer');

        return $this->redirect($referer ?: $this->generateUrl('catalog_index'));
    }

    // ── Eliminar del carrito ───────────────────────────────────────────

    #[Route('/cart/remove/{id}', name: 'cart_remove', methods: ['POST'])]
    public function removeFromCart(int $id, CartService $cart): Response
    {
        $cart->remove($id);

        $this->addFlash('success', 'Producto eliminado del carrito.');

        return $this->redirectToRoute('cart_index');
    }

    // ── Ver carrito ────────────────────────────────────────────────────

    #[Route('/cart', name: 'cart_index', methods: ['GET'])]
    public function cart(CartService $cart): Response
    {
        return $this->render('catalog/cart.html.twig', [
            'items' => $cart->getCartWithData(),
            'total' => $cart->getTotal(),
        ]);
    }
}
