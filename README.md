# arfnet2-cstims
ARFNET2 Client, Service, Ticket and Invoice Management System

Depends on PHPMailer

```
User types:
    Admin:      user, service and ticket management
    Helpdesk:   read, answer and close tickets
    Accountant: view invoices and change status
    Client:     order services and open tickets
Service types:
    premium, standard, free

Report problems with CST or such as password changes to admin@arf20.com

FILES:
    index.html
        landing page, branding?

    register.php -> login.php
        registers client to db
        send email with verification link
    login.php -> { client.php, helpdesk.php, admin.php }
        checks creds against db and starts session
        checks user type for location
    logout.php -> login.php
        stops session

    verify.php -> login.php
        from a link, has the base64 code generated at registration sent to email for verification

    client.php -> { order.php, openticket.php }
        shows ordered services and opened tickets
    helpdesk.php
        view, self-assign and close tickets
    accountant.php
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
        manageorders.php
            form to add, edit and delete order entries
        managetickets.php
            form to add, edit and delete ticket entries (assign, too, sends email to specific helpdesk person)
        manageinvoices.php
            form to add, edit and delete invoice entries
    
    Everyday crontab:
        makeinvoices.php
            when billing for a service is due, generates invoice and sends to client and accounting@arf20.com list


SQL:
Tables:
    users       User logins
        id autoincrement, username, password (hash), email, email verification code, status { verified, unverified }, type { client, helpdesk, accountant, admin }, register date
    services    Available services
        id autoincrement, name, type, billing, description
    orders      List of user orders and management notes etc
        id autoincrement, service id, instance name, client id, order date, specific billing, comments
    tickets     List of tickets
        id autoincrement, order id, subject, body, status { open, closed, nofix }, asignee
    invoices    List of invoices
        id autoincrement, order id, description, date, pdf, status { paid, unpaid }
```
