Commands
========

When you install Instance Manager with composer it will create file "im" in folder /bin
You can use it to call command like:

.. : code
    php bin/im database:pull dev


Database
^^^^^^^^

  database:pull <instance>
    Pull database from instance passed as param.

  database:push <databaseCode>
    Push database to proxy account.

Media
^^^^^

  media:pull <instance>
    Pull media from instance passed as param.

  media:push
    Push media to proxy account.


Release
^^^^^^^

  release:make <instance>
    Make new application release on instance passed as param.

Ssh
^^^

  ssh:connect <instance>
    Make ssh connection to instance passed as param.

