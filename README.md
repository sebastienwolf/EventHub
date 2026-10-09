# EventHub

A REST API to manage events (conferences and workshops) in the spirit of Meetup, built with **Symfony 7.4 LTS**.

The goal of this project is to show how the Laravel concepts I use every day map to idiomatic Symfony code:
explicit dependency injection, DTOs, voters, Doctrine listeners, Messenger, Scheduler and more, with no API Platform magic hiding the controllers.

## Features

- **Users**: registration, JWT login, roles (admin > organizer > participant)
- **Events**: organizers create, update, publish and cancel conferences and workshops
- **Registrations**: participants register; a confirmation email is queued and the organizer is notified
- **Reminders**: a scheduled task emails participants 24 hours before the event
- **i18n**: API messages and emails in English or French, based on the `Accept-Language` header

## Laravel → Symfony

| Laravel | Symfony | In this project |
|---|---|---|
| Eloquent model | Doctrine entity + repository | [src/Entity](src/Entity), [src/Repository](src/Repository) |
| Model inheritance | Doctrine Single Table Inheritance + mapped superclass | [Event](src/Entity/Event.php) → [Conference](src/Entity/Conference.php) / [Workshop](src/Entity/Workshop.php), [TimestampableEntity](src/Entity/TimestampableEntity.php) |
| Migration | Doctrine Migrations | [migrations](migrations) |
| Factory / Seeder | Foundry + DoctrineFixturesBundle | [src/Factory](src/Factory), [AppStory](src/Story/AppStory.php) |
| FormRequest | DTO + Validator constraints + `#[MapRequestPayload]` | [src/Dto](src/Dto) |
| API Resource | Serializer groups + `#[Context]` | `#[Groups]` in [src/Entity](src/Entity) |
| Policy / Gate | Voter + `#[IsGranted]` | [EventVoter](src/Security/Voter/EventVoter.php) |
| Middleware | Subscriber on `kernel.request` / `kernel.response` | [LocaleSubscriber](src/EventSubscriber/LocaleSubscriber.php) |
| Event / Listener | EventDispatcher + `#[AsEventListener]` | [ParticipantRegistered](src/DomainEvent/ParticipantRegistered.php), [src/EventListener](src/EventListener) |
| Observer | Doctrine listener / entity listener | [TimestampableListener](src/Doctrine/Listener/TimestampableListener.php), [EventSlugListener](src/Doctrine/Listener/EventSlugListener.php) |
| Mailable | Mailer + `TemplatedEmail` (Twig) | [handlers](src/MessageHandler), [templates/emails](templates/emails) |
| Notification | Notifier component | [NewRegistrationNotification](src/Notification/NewRegistrationNotification.php) |
| Job / Queue | Messenger (Doctrine transport) | [src/Message](src/Message), [src/MessageHandler](src/MessageHandler) |
| Task scheduling | Scheduler component | [ReminderSchedule](src/Scheduler/ReminderSchedule.php) |
| `lang/` files | Translation component (YAML) | [translations](translations) |
| Service provider / container | Autowiring + `services.yaml` | [config/services.yaml](config/services.yaml) |
| Route model binding | `#[MapEntity]` / EntityValueResolver | [EventController](src/Controller/EventController.php) |
| Exception handler | Listener on `kernel.exception` | [ApiExceptionListener](src/EventListener/ApiExceptionListener.php) |
| Artisan command | `#[AsCommand]` console command | [src/Command](src/Command) |
| Sanctum | LexikJWTAuthenticationBundle | [security.yaml](config/packages/security.yaml) |
| Rate limiting | RateLimiter component | [rate_limiter.yaml](config/packages/rate_limiter.yaml), `login_throttling` |
| `helpers.php` | Injectable service (idiomatic) or Composer `autoload.files` | [DurationFormatter](src/Service/DurationFormatter.php), [helpers.php](src/helpers.php) |
| Base controller | Abstract controller with shared JSON and pagination helpers | [AbstractApiController](src/Controller/AbstractApiController.php) |

## Requirements

- PHP 8.2+ with `intl`, `pdo_mysql` and `sodium`
- Composer
- Docker (MySQL 8.4 and Mailpit)
- [Symfony CLI](https://symfony.com/download) (optional, for the local web server)

## Getting started

```bash
composer install
docker compose up -d                        # MySQL on port 3307, Mailpit on 1025/8025

php bin/console lexik:jwt:generate-keypair  # keys are stored in config/jwt (git-ignored)
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load      # demo data

symfony serve -d                            # or: php -S 127.0.0.1:8000 -t public
php bin/console messenger:consume async scheduler_reminders -vv   # queue worker + scheduler
```

Emails sent by the worker are visible in Mailpit: http://localhost:8025.

> **Windows**: if the key generation fails with `error:80000003:system library`, point OpenSSL to a config file first,
> for example `set OPENSSL_CONF=C:\wamp64\bin\apache\apache2.4.59\conf\openssl.cnf`.

### Demo accounts

All accounts use the password `password`.

| Email | Role |
|---|---|
| admin@eventhub.test | Admin |
| organizer@eventhub.test | Organizer (French) |
| participant@eventhub.test | Participant (French) |

## API

| Method | Endpoint | Access | Description |
|---|---|---|---|
| POST | `/api/auth/register` | Public, rate limited | Create an account (`accountType`: `participant` or `organizer`) |
| POST | `/api/auth/login` | Public, throttled | Returns a JWT: `{"token": "..."}` |
| GET | `/api/me` | Authenticated | Current user |
| GET | `/api/events?type=&page=&limit=` | Public | Upcoming published events, paginated |
| GET | `/api/events/{slug}` | Public if published | Event details |
| POST | `/api/events` | Organizer | Create a conference or a workshop (draft) |
| PATCH | `/api/events/{id}` | Owner or admin | Partial update |
| POST | `/api/events/{id}/publish` | Owner or admin | Publish a draft |
| POST | `/api/events/{id}/cancel` | Owner or admin | Cancel the event |
| DELETE | `/api/events/{id}` | Owner or admin | Delete the event |
| GET | `/api/me/events` | Organizer | My events, any status |
| POST | `/api/events/{id}/registrations` | Authenticated | Register to an event |
| DELETE | `/api/events/{id}/registrations` | Authenticated | Cancel my registration |
| GET | `/api/events/{id}/registrations` | Owner or admin | Participants list |
| GET | `/api/me/registrations` | Authenticated | My registrations |

Send `Authorization: Bearer <token>` on authenticated routes and `Accept-Language: fr` to get French messages.

```bash
TOKEN=$(curl -s -X POST http://127.0.0.1:8000/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"organizer@eventhub.test","password":"password"}' | jq -r .token)

curl -X POST http://127.0.0.1:8000/api/events \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' -H 'Accept-Language: fr' \
  -d '{"type":"workshop","title":"Symfony for Laravel developers","description":"Hands-on",
       "startsAt":"2030-06-01T09:00:00+02:00","endsAt":"2030-06-01T12:00:00+02:00",
       "location":"Brussels","capacity":12,"level":"beginner"}'
```

Errors share a single format:

```json
{"error": {"code": 422, "message": "Les données envoyées sont invalides.", "violations": [{"property": "level", "message": "Cette valeur ne doit pas être nulle."}]}}
```

## Console commands

```bash
php bin/console app:user:promote someone@example.com admin   # grant the organizer or admin role
php bin/console app:reminders:send                           # run the reminder task now
php bin/console debug:scheduler                              # list scheduled tasks
```

## Tests

```bash
php bin/console doctrine:database:create --env=test --if-not-exists
php bin/console doctrine:migrations:migrate --env=test -n
php bin/phpunit
```

- **Unit**: helpers, voter, locale subscriber
- **Functional**: every endpoint through HTTP with real JWTs, Foundry factories and DAMA transaction rollback
- **Integration**: reminder scheduling and localized emails

The same steps run on GitHub Actions ([ci.yml](.github/workflows/ci.yml)).

## Technical notes

- All dates are stored in UTC (MySQL `DATETIME` has no timezone).
- Every email goes through the `async` Messenger transport, so no HTTP request waits for SMTP.
- Registrations are protected by a lock so two participants cannot book the last seat at the same time.
