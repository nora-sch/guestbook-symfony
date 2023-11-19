<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentType;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
// use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
// use Twig\Environment;

class ConferenceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $bus,
    ) {
    }
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
    public function show(
        Request $request,
        Conference $conference,
        CommentRepository $commentRepository,
        ConferenceRepository $conferenceRepository,
        // SpamChecker $spamChecker, 
        #[Autowire('%photo_dir%')] string $photoDir
    ): Response {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setConference($conference);
            if ($photo = $form['photo']->getData()) {
                $filename = bin2hex(random_bytes(6)) . '.' . $photo->guessExtension();
                $photo->move($photoDir, $filename);
                $comment->setPhotoFilename($filename);
            }
            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $context = [
                'user_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referrer' => $request->headers->get('referer'),
                'permalink' => $request->getUri(),
            ];
            // dump($spamChecker->getSpamScore($comment, $context));
            // if (2 === $spamChecker->getSpamScore($comment, $context)) {
            //     throw new \RuntimeException('Blatant spam, go away!');
            // }
            // $this->entityManager->flush();
            $this->bus->dispatch(new CommentMessage($comment->getId(), $context));
            return $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
        }
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
            'comment_form' => $form,
            // ]));
        ]);
    }
}
