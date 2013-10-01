# PHP_CodeBrowser #

## Structure ##

    |--> bin/           PHP_CodeBrowser scripts
    |--> src/           Source files for PHP_CodeBrowser
    |   |--> Plugins/   Plugins for different error handling/types
    |
    |--> templates/     Template files for PHP_CodeBrowser
    |   |--> css/       Used CSS by templates, Color definition for errors
    |   |--> img/       Used images for PHP_CodeBrowser
    |   |--> js/        Used javascript for PHP_CodeBrowser
    |
    |--> tests/         PHPUnit test suite
    |
    |--> package.xml    PEAR package information file
    |
    |--> LICENCE        Licence information
    |--> README         Structure and install information
    |--> CHANGELOG      Update information

## Installation ##

### Git Checkout ###

    $ git clone git://github.com/Mayflower/PHP_CodeBrowser.git

### Installation with PEAR Installer ###

    $ pear channel-discover pear.phpqatools.org
    $ pear install --alldeps phpqatools/PHP_CodeBrowser

## Usage ##

### Shell Usage ###

    Try ./bin/phpcb.php -h for usage information.

### Integration in Jenkins, CruiseControl and Hudson ###

    ...
    <!-- phpcb should be called after xml file generation -->
    <target name="build" depends="...,phpcb" />
    ...
    <target name="phpcb">
        <exec executable="phpcb">
            <arg line="--log path/to/log/dir
                       --output path/to/output/dir/
                       --source path/to/source/dir/" />
        </exec>
    </target>
    ...

## View the Results ##

### Webbrowser ###

Open `/path/to/defined/output/index.html`.

### CruiseControl ###

#### config.xml ####

    <publishers>
      <artifactspublisher dir="path/to/output" dest="artifacts/${project.name}" subdirectory="PhpCbIdentifier" />
      ...
    </publishers>

#### main.jsp ####

    <cruisecontrol:tab name="PHP_CodeBrowser" label="PHP_CodeBrowser">
      <cruisecontrol:artifactsLink>
         <iframe src="<%=request.getContextPath() %>/<%= artifacts_url %>/PhpCbIdentifier/index.html" class="tab-content">
         </iframe>
      </cruisecontrol:artifactsLink>
    </cruisecontrol:tab>

### Jenkins/Hudson ###

Have a look at the [standard template for Jenkins jobs for PHP projects](https://github.com/sebastianbergmann/php-jenkins-template) to see how PHP_CodeBrowser can be used together with Jenkins.

## Contact Information ##

If you have any questions you may get in contact with: Elger Thiele <elger DOT thiele AT mayflower DOT de> or Thorsten Rinne <thorsten DOT rinne AT mayflower DOT de>
