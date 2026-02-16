<?php

namespace App\Service;

use App\Entity\Producto;
use App\Repository\ProductoRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Gestiona el carrito de compras mediante la sesión HTTP.
 *
 * Estructura en sesión: ['cart' => [productoId => cantidad, …]]
 */
class CartService
{
    private const SESSION_KEY = 'cart';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ProductoRepository $productoRepository,
    ) {
    }

    // ── Operaciones básicas ────────────────────────────────────────────

    /**
     * Añade (o incrementa) un producto al carrito.
     * Devuelve la cantidad realmente añadida (puede ser menor si se supera el stock).
     */
    public function add(int $productoId, int $quantity = 1): int
    {
        $producto = $this->productoRepository->find($productoId);
        if (!$producto) {
            return 0;
        }

        $cart = $this->getCart();
        $currentQty = $cart[$productoId] ?? 0;
        $maxStock = $producto->getStock();
        $available = max(0, $maxStock - $currentQty);
        $toAdd = min($quantity, $available);

        if ($toAdd > 0) {
            $cart[$productoId] = $currentQty + $toAdd;
            $this->saveCart($cart);
        }

        return $toAdd;
    }

    /**
     * Elimina un producto del carrito.
     */
    public function remove(int $productoId): void
    {
        $cart = $this->getCart();
        unset($cart[$productoId]);
        $this->saveCart($cart);
    }

    /**
     * Vacía el carrito completo.
     */
    public function clear(): void
    {
        $this->saveCart([]);
    }

    // ── Consultas ──────────────────────────────────────────────────────

    /**
     * Devuelve el carrito crudo: [id => qty, …]
     *
     * @return array<int, int>
     */
    public function getCart(): array
    {
        return $this->getSession()->get(self::SESSION_KEY, []);
    }

    /**
     * Devuelve el carrito hidratado con objetos Producto.
     *
     * @return array<int, array{producto: Producto, quantity: int}>
     */
    public function getCartWithData(): array
    {
        $cart = $this->getCart();
        $detailed = [];

        foreach ($cart as $id => $qty) {
            $producto = $this->productoRepository->find($id);
            if ($producto) {
                $detailed[] = [
                    'producto' => $producto,
                    'quantity' => $qty,
                ];
            }
        }

        return $detailed;
    }

    /**
     * Calcula el precio total del carrito.
     */
    public function getTotal(): float
    {
        $total = 0.0;

        foreach ($this->getCartWithData() as $item) {
            $total += (float) $item['producto']->getPrecio() * $item['quantity'];
        }

        return $total;
    }

    /**
     * Devuelve el número total de artículos en el carrito.
     */
    public function getCount(): int
    {
        return array_sum($this->getCart());
    }

    // ── Helpers privados ───────────────────────────────────────────────

    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }

    /**
     * @param array<int, int> $cart
     */
    private function saveCart(array $cart): void
    {
        $this->getSession()->set(self::SESSION_KEY, $cart);
    }
}
