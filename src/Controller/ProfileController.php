<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig');
    }

    #[Route('/profile/password', name: 'app_profile_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $current = $request->request->get('current_password', '');
        $newPass = $request->request->get('new_password', '');
        $confirm = $request->request->get('confirm_password', '');

        // Validar contraseña actual
        if (!$hasher->isPasswordValid($user, $current)) {
            $this->addFlash('danger', 'La contraseña actual no es correcta.');
            return $this->redirectToRoute('app_profile');
        }

        // Validar nueva contraseña
        if (strlen($newPass) < 6) {
            $this->addFlash('danger', 'La nueva contraseña debe tener al menos 6 caracteres.');
            return $this->redirectToRoute('app_profile');
        }

        if ($newPass !== $confirm) {
            $this->addFlash('danger', 'Las contraseñas no coinciden.');
            return $this->redirectToRoute('app_profile');
        }

        // Actualizar contraseña
        $user->setPassword($hasher->hashPassword($user, $newPass));
        $em->flush();

        $this->addFlash('success', 'Contraseña actualizada correctamente.');

        return $this->redirectToRoute('app_profile');
    }
}
