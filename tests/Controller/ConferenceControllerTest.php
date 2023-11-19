<?php

namespace App\Tests\Controller;

use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
// use Symfony\Component\Panther\PantherTestCase; // COULDN'T install because of version of php  - TODO later 'symfony composer req panther --dev'

class ConferenceControllerTest extends WebTestCase
// class ConferenceControllerTest extends PantherTestCase
{
    public function testIndex()
    {
        $client = static::createClient();
        // $client = static::createPantherClient(['external_base_uri' => rtrim($_SERVER['SYMFONY_PROJECT_DEFAULT_ROUTE_URL'], '/')]);
        $client->request('GET', '/');
        $client->request('GET', '/');
        // echo($client->getResponse());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Give your feedback');
        // $this->assertSelectorTextContains('h1', 'Give your feedback'); // gives error
    }

    // public function testConferencePage()
    // {
    //     $client = static::createClient();
    //     $crawler = $client->request('GET', '/');

    //     $this->assertCount(2, $crawler->filter('h4'));

    //     $client->clickLink('View');

    //     $this->assertPageTitleContains('Amsterdam');
    //     $this->assertResponseIsSuccessful();
    //     $this->assertSelectorTextContains('h2', 'Amsterdam 2019');
    //     $this->assertSelectorExists('div:contains("There are 1 comments")');
    // }

    public function testCommentSubmission()
    {
        $client = static::createClient();
        $client->request('GET', '/conference/amsterdam-2019');
        $client->submitForm('Submit', [
            'comment[author]' => 'Fabien', // in example - 'comment_form[author]', but it doesn't work because verified in inspector of browser - my form name is 'comment'
            'comment[text]' => 'Some feedback from an automated functional test',
            // 'comment[email]' => 'me@automat.ed',
            'comment[email]' => $email = 'me@automat.ed',
            'comment[photo]' => dirname(__DIR__, 2) . '/public/images/under-construction.gif',
        ]);
        $this->assertResponseRedirects();

        // simulate comment validation
        $comment = self::getContainer()->get(CommentRepository::class)->findOneByEmail($email);
        $comment->setState('published');
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $client->followRedirect();
        $this->assertSelectorExists('div:contains("There are 2 comments")');
    }

    public function testConferencePage()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertCount(2, $crawler->filter('h4'));

        $client->clickLink('View');

        $this->assertPageTitleContains('Amsterdam');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Amsterdam 2019');
        $this->assertSelectorExists('div:contains("There are 1 comments")');
    }
}
