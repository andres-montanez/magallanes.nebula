<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserGroup;
use App\Form\Type\UserGroupType;
use App\Form\Type\UserType;
use App\Service\UserGroupService;
use App\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    protected UserService $userService;
    protected UserGroupService $groupService;

    public function __construct(UserService $userService, UserGroupService $groupService)
    {
        $this->userService = $userService;
        $this->groupService = $groupService;
    }

    /**
     * @Route("/users", name="mage_users")
     */
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER_LIST');

        $users = $this->userService->getUsers();
        $groups = $this->groupService->getGroups();

        return $this->render('users/index.html.twig', [
            'users' => $users,
            'groups' => $groups
        ]);
    }

    /**
     * @Route("/user/new", name="mage_user_new")
     */
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER_NEW');

        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->userService->create($user);

            $this->addFlash('success', sprintf('User %s created', $user->getName()));

            return $this->redirectToRoute('mage_users');
        }

        return $this->render('users/detail.html.twig', [
            'user' => $user,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/user/{id}", name="mage_user_detail")
     */
    public function detail(User $user, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER_EDIT');

        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->userService->update($user);

            $this->addFlash('success', sprintf('User %s updated', $user->getName()));

            return $this->redirectToRoute('mage_users');
        }

        return $this->render('users/detail.html.twig', [
            'user' => $user,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/user-group/new", name="mage_user_group_new")
     */
    public function newGroup(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER_GROUP_NEW');

        $group = new UserGroup();
        $form = $this->createForm(UserGroupType::class, $group);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->groupService->create($group);

            $this->addFlash('success', sprintf('Group %s created', $group->getName()));

            return $this->redirectToRoute('mage_users');
        }

        return $this->render('users/group.html.twig', [
            'group' => $group,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/user-group/{id}", name="mage_user_group_detail")
     */
    public function detailGroup(UserGroup $group, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER_GROUP_EDIT');

        $form = $this->createForm(UserGroupType::class, $group);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->groupService->update($group);

            $this->addFlash('success', sprintf('Group %s updated', $group->getName()));

            return $this->redirectToRoute('mage_users');
        }

        return $this->render('users/group.html.twig', [
            'group' => $group,
            'form' => $form->createView()
        ]);
    }

}