<html>
    <head>
        <style>
            div
            {
                margin: 15px;
                padding: 5px;
                border: 1px solid #FFFF00;
                background-color: #000000;
                color: #FFFF00;
            }

            pre
            {
                margin: 15px;
                padding: 5px;
                border: 1px solid #FFFFFF;
                background-color: #000000;
                color: #FFFFFF;
            }

            form
            {
                text-align: center;
            }

            input
            {
                border: 1px dotted #FF0000;
                background-color: #000000;
                color: #FF0000;
            }
        </style>
        <title>
            eCash3.0 Database Cache Management
        </title>
    </head>
    <body style="background-color: #000000;">
        <div>
            Notes regarding usage:
            <br>
            <br>
            This utility should only be used when you made database changes.
            The only reason to use this utility is if you've changed the data which is typically cached.
            There are many queries which are cached, but very few tables covered by those queries.
        </div>
        <?php

            require_once(dirname(realpath(__FILE__))."/config.php");

            function system_block($command)
            {
                print("<pre>");
                print("$command\n\n");
                system($command);
                print("</pre>");
            }

            if(empty(ECash::getConfig()->DB_CACHE))
            {
                print("DB_CACHE configuraton not found");
            }
            elseif(FALSE === realpath(ECash::getConfig()->DB_CACHE))
            {
                print(ECash::getConfig()->DB_CACHE." not found");
            }
            else
            {
                $path = realpath(ECash::getConfig()->DB_CACHE);
                $path = escapeshellarg($path);

                system_block("du -h -s $path # SIZE BEFORE DELETION");

                if(isset($_POST) && isset($_POST['clear']) && $_POST['clear'])
                {
                    system_block("find $path -type f -exec rm -vf {} \\; # DELETE FILES LEAVING DIRECTORIES INTACT");
                }
            }

        ?>
        <form method="post">
            <input type="hidden" name="clear" value="1">
            <input type="submit" value="Empty the cache, please">
        </form>
    </body>
</html>
