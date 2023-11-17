<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
// use Twig\Environment;

class ConferenceController extends AbstractController
{
    // #[Route('/conference', name: 'app_conference')]
    #[Route('/', name: 'homepage')]
    // public function index(): Response
    // {
    //     // return $this->render('conference/index.html.twig', [
    //     //     'controller_name' => 'ConferenceController',
    //     // ]);
    //     return new Response(<<<EOF
    //         <html>
    //             <body>
    //                 <img src="/images/under-construction.gif" />
    //            </body>
    //         </html>
    //         EOF
    //     );
    // }
    // public function index(Environment $twig, ConferenceRepository $conferenceRepository): Response
    public function index(ConferenceRepository $conferenceRepository): Response
    {
        // return new Response($twig->render('conference/index.html.twig', [
        return $this->render('conference/index.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
            // ]));
        ]);
    }

    #[Route('/conference/{slug}', name: 'conference')]
    // public function show(Environment $twig, Conference $conference, CommentRepository $commentRepository)
    // public function show(Request $request, Environment $twig, Conference $conference, CommentRepository $commentRepository): Response
    public function show(Request $request, Conference $conference, CommentRepository $commentRepository, ConferenceRepository $conferenceRepository): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $offset = max(0, $request->query->getInt('offset', 0));
        $paginator = $commentRepository->getCommentPaginator($conference, $offset);
        // return new Response($twig->render('conference/show.html.twig', [
        return $this->render('conference/show.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
            'conference' => $conference,
            // 'comments' => $commentRepository->findBy(['conference' => $conference], ['createdAt' => 'DESC']),
            'comments' => $paginator,
            'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
            'next' => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE),
            'comment_form'=>$form,
            // ]));
        ]);
    }
}
