<?php

namespace App\Controller;

use App\Entity\Producto;
use App\Form\ProductoType;
use App\Repository\ProductoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    // ── Lista de productos ─────────────────────────────────────────────

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ProductoRepository $repo): Response
    {
        return $this->render('admin/index.html.twig', [
            'productos' => $repo->findAll(),
        ]);
    }

    // ── Crear producto ─────────────────────────────────────────────────

    #[Route('/producto/new', name: 'producto_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
    ): Response {
        $producto = new Producto();
        $form = $this->createForm(ProductoType::class, $producto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $producto, $slugger);

            $em->persist($producto);
            $em->flush();

            $this->addFlash('success', 'Producto creado correctamente.');

            return $this->redirectToRoute('admin_index');
        }

        return $this->render('admin/new.html.twig', [
            'form' => $form,
        ]);
    }

    // ── Editar producto ────────────────────────────────────────────────

    #[Route('/producto/{id}/edit', name: 'producto_edit', methods: ['GET', 'POST'])]
    public function edit(
        Producto $producto,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
    ): Response {
        $form = $this->createForm(ProductoType::class, $producto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $producto, $slugger);

            $em->flush();

            $this->addFlash('success', 'Producto actualizado correctamente.');

            return $this->redirectToRoute('admin_index');
        }

        return $this->render('admin/edit.html.twig', [
            'form' => $form,
            'producto' => $producto,
        ]);
    }

    // ── Borrar producto ────────────────────────────────────────────────

    #[Route('/producto/{id}/delete', name: 'producto_delete', methods: ['POST'])]
    public function delete(
        Producto $producto,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        // Protección CSRF
        if ($this->isCsrfTokenValid('delete' . $producto->getId(), $request->request->get('_token'))) {
            // Eliminar fichero de imagen si existe
            $imagen = $producto->getImagen();
            if ($imagen) {
                $path = $this->getParameter('kernel.project_dir') . '/public/uploads/productos/' . $imagen;
                if (file_exists($path)) {
                    unlink($path);
                }
            }

            $em->remove($producto);
            $em->flush();

            $this->addFlash('success', 'Producto eliminado correctamente.');
        }

        return $this->redirectToRoute('admin_index');
    }

    // ── Helpers ────────────────────────────────────────────────────────

    /**
     * Gestiona la subida de la imagen del producto.
     */
    private function handleImageUpload(
        \Symfony\Component\Form\FormInterface $form,
        Producto $producto,
        SluggerInterface $slugger,
    ): void {
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $imagenFile */
        $imagenFile = $form->get('imagenFile')->getData();

        if ($imagenFile) {
            $originalFilename = pathinfo($imagenFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imagenFile->guessExtension();

            try {
                $imagenFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/productos',
                    $newFilename,
                );
            } catch (FileException $e) {
                $this->addFlash('danger', 'Error al subir la imagen: ' . $e->getMessage());

                return;
            }

            // Borrar imagen anterior si existe
            $oldImage = $producto->getImagen();
            if ($oldImage) {
                $oldPath = $this->getParameter('kernel.project_dir') . '/public/uploads/productos/' . $oldImage;
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $producto->setImagen($newFilename);
        }
    }
}
