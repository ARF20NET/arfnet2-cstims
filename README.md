# arfnet2-csti
ARFNET2 Client, Service, Ticket and Invoice management system

```
User types:
    Admin:      user, service and ticket management
    Helpdesk:   read, answer and close tickets
    Accountant: view invoices and change status
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
        view, self-assign and close tickets
    accounting.php
        view invoices and change status
    admin.php -> { manageusers.php, manageservices.php, managetickets.php }
        shows users, services, tickets and invoices

    Client:
        order.php
            add service to account, sends mail to admin@arf20.com to deploy manually
            billing is automated
        openticket.php
            open ticket linked to account and service, sends mail to helpdesk@arf20.com list
            then helpdesk answers email and stuff
    
    Admin:
        manageusers.php
            form to add, edit and delete user entries
        manageservices.php
            form to add, edit and delete service entries
        managetickets.php
            form to add, edit and delete ticket entries (assign, too, sends email to specific helpdesk person)
        manageinvoices.php
            form to add, edit and delete invoice entries
    
    Everyday crontab:
        makeinvoices.php
            when billing for a service is due, generates invoice and sends to client and accounting@arf20.com list


SQL:
Database: arfnet2
Tables:
    users       User logins
        id autoincrement, username, password (hash), email, email verification code, user type { client, helpdesk, accountant, admin }, register date
    services    Available services and management notes etc
        id autoincrement, name, type, billing, description
    orders
        id autoincrement, service id, instance name, client id, order date, specific billing, comments
    tickets     List of tickets
        id autoincrement, client id, title, body, status { open, closed, nofix }, asignee
    invoices    List of invoices
        id autoincrement, client id, service id, bill amount, description, date, status { paid, unpaid }
```
