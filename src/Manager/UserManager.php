<?php
/**
 * Created by PhpStorm
 * User: shadowluffy
 * Date: 10/9/20
 * Time: 8:16 PM
 */

namespace App\Manager;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Class UserManager
 * @package App\Manager
 *
 * @author CONTE Alexandre <pro.alexandre.conte@gmail.com>
 */
class UserManager
{
    protected $em;
    protected $usersRepository;
    protected $passwordEncoder;
    protected $logger;

    /**
     * UserManager constructor.
     *
     * @param EntityManagerInterface $manager
     * @param UserRepository $usersRepository
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntityManagerInterface $manager,
        UserRepository $usersRepository,
        UserPasswordEncoderInterface $passwordEncoder,
        LoggerInterface $logger)
    {
        $this->em = $manager;
        $this->usersRepository = $usersRepository;
        $this->passwordEncoder = $passwordEncoder;
        $this->logger = $logger;
    }

    /**
     * Create User
     *
     * @return User
     */
    public function create()
    {
        return new User();
    }

    /**
     * @param string $username
     *
     * @return User|object|null
     */
    public function getUserByUsername(string $username)
    {
        $user = null;

        try {
            $user = $this->usersRepository->findOneBy(['username' => $username]);
        } catch (NonUniqueResultException $exception) {
            $this->logger->error(sprintf('Multiple user returned with the same username: %s', $username));
        } catch (NoResultException $exception) {
        }

        return $user;
    }

    /**
     * Update password user with plainPassword who is the data form
     *
     * @param User $user
     *
     * @throws \Exception
     */
    public function updatePassword(User $user): void
    {
        if (0 !== strlen($password = $user->getPlainPassword())) {
            $user->setSalt(rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '='));
            $user->setPassword($this->passwordEncoder->encodePassword($user, $user->getPlainPassword()));
            $user->eraseCredentials();
        }
    }

    /**
     * Save User
     *
     * @param User $users
     * @param bool $andFlush
     *
     * @throws \Exception
     */
    public function save(User $users, $andFlush = true): void
    {
        $this->updatePassword($users);

        $this->em->persist($users);
        if ($andFlush) {
            $this->em->flush();
        }
    }
}