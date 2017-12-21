<?php

namespace Packagist\WebBundle\Tests\Controller;

use Exception;
use Packagist\WebBundle\Entity\Package;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WebControllerTest extends WebTestCase
{
    public function testHomepage()
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/');
        $this->assertEquals('Getting Started', $crawler->filter('.getting-started h2')->text());
    }

    public function testPackages()
    {
        $client = self::createClient();

        $this->initializePackages($client->getContainer());

        //we expect at least one package
        $crawler = $client->request('GET', '/packages/');
        $this->assertTrue($crawler->filter('.packages li')->count() > 0);
    }

    public function testPackage()
    {
        $client = self::createClient();

        $this->initializePackages($client->getContainer());

        //we expect package to be clickable and showing at least 'package' div
        $crawler = $client->request('GET', '/packages/');
        $link = $crawler->filter('.packages li a')->first()->attr('href');

        $crawler = $client->request('GET', $link);
        $this->assertTrue($crawler->filter('.package')->count() > 0);
    }

    protected function initializePackages(ContainerInterface $container)
    {
        $kernelRootDir = $container->getParameter('kernel.root_dir');

        $this->executeCommand('php '.$kernelRootDir . '/console doctrine:database:drop --env=test --force', false);
        $this->executeCommand('php '.$kernelRootDir . '/console doctrine:database:create --env=test');
        $this->executeCommand('php '.$kernelRootDir . '/console doctrine:schema:create --env=test');
        $this->executeCommand('php '.$kernelRootDir . '/console redis:flushall --env=test -n');

        $em = $container->get('doctrine')->getManager();

        $twigPackage = new Package();

        $twigPackage->setName('twig/twig');
        $twigPackage->setRepository('https://github.com/twig/twig');

        $packagistPackage = new Package();

        $packagistPackage->setName('composer/packagist');
        $packagistPackage->setRepository('https://github.com/composer/packagist');

        $symfonyPackage = new Package();

        $symfonyPackage->setName('symfony/symfony');
        $symfonyPackage->setRepository('https://github.com/symfony/symfony');

        $em->persist($twigPackage);
        $em->persist($packagistPackage);
        $em->persist($symfonyPackage);

        $em->flush();

        return [$twigPackage, $packagistPackage, $symfonyPackage];
    }

    /**
     * Executes a given command.
     *
     * @param string $command a command to execute
     * @param bool $errorHandling
     *
     * @throws Exception when the return code is not 0.
     */
    protected function executeCommand(
        $command,
        $errorHandling = true
    ) {
        $output = array();

        $returnCode = null;;

        exec($command, $output, $returnCode);

        if ($errorHandling && $returnCode !== 0) {
            throw new Exception(
                sprintf(
                    'Error executing command "%s", return code was "%s".',
                    $command,
                    $returnCode
                )
            );
        }
    }

    /**
     * @param string $package
     * @param int $downloads
     * @param int $favers
     *
     * @return array
     */
    protected function getJsonResult($package, $downloads, $favers)
    {
        return array(
            'name' => $package,
            'description' => '',
            'url' => 'http://localhost/packages/' . $package,
            'repository' => 'https://github.com/' . $package,
            'downloads' => $downloads,
            'favers' => $favers,
        );
    }

    /**
     * @param array $results
     *
     * @return array
     */
    protected function getJsonResults(
        array $results
    ) {
        return array(
            'results' => $results,
            'total' => count($results)
        );
    }
}
