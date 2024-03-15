# arfnet2-cst
ARFNET2 Client, Service manager and Ticketing system

```
User types:
    Admin:      user, service and ticket management
    Helpdesk:   read, answer and close tickets only
    Client:     order services and open tickets

Report problems with CST or such as password changes to admin@arf20.com

FILES:
    register.php -> login.php
        registers client to db
        send email with verification link
    login.php -> { client.php, helpdesk.php, admin.php }
        checks creds against db and starts session
        checks user type for location
    logout.php -> login.php
        stops session

    client.php -> { order.php, openticket.php }
        shows ordered services and opened tickets
    helpdesk.php
        view and close tickets
    admin.php
        manage users, services and tickets

SQL:
Database: arfnet2
Tables:
    users       User logins
        id autoincrement, username, password (hash), email, email verification code, user type { client, helpdesk, admin }, register date
    services    Available services and management notes etc
        id autoincrement, name, type, billing, description
    orders
        id autoincrement, service id, instance name, client id, comments
    tickets     List of tickets
        id autoincrement, client id, title, body, status { open, closed, nofix }

```
