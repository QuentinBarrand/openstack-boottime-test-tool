# OpenStack booting time test tool

Web application for testing, saving and displaying the boot time of instances in an OpenStack-based infrastructure. This app allows you to boot a specified amount of instances of a specified image and flavor and to view the results in frequency histograms or to export them in a CSV file.


### Requirements 
* `python-novaclient` (tested with version 2.13.0) ([GitHub](https://github.com/openstack/python-novaclient), [PyPI](https://pypi.python.org/pypi/python-novaclient))
* MySQL database server


## Installation
1.	Copy the two `external` and `src` folders into your apache DocumentRoot directory.

2.	Download the [Slim](https://github.com/codeguy/Slim/) and [PHPlot](http://sourceforge.net/projects/phplot/) frameworks and extract them into the `external` directory.

3.	Add the following line to your `virtualhost`'s configuration file (or to `httpd.conf` on Red Had-based hosts) :
	`Alias /openstack-boottime/ /var/www/openstack-boottime-tool/src/webroot/`

4.	Create the database using the `src/sql/spawntime.sql` file.

5.	Fill the `src/config/config.php` file with your MySQL and OpenStack credentials (can be obtained by downloading `openrc.sh` from your Horizon interface).

6.	Perform the following commands (as `root`) to get the installation working and secure :

	* `# chown -R <your_user>:<apache_user> /path/to/your/installation/`
	* `# chmod -R 770 /path/to/your/installation/`

7.	Optionnal : If you're running Red Hat-based Linux, you may also have to modify some SELinux policies :

	* `# setsebool httpd_can_network_connect 1`
	* `# setsebool httpd_can_sendmail 1` (optionnal : if you want an e-mail when the test is over)


## How to use
Simply query the following URLs using your favorite browser :

#### Launch a new test
* Pet machines (with DNS registration) `/openstack-boottime/index.php/new/<image_id>/<flavor_id>/<rounds>`
* Cattle machines (without DNS registration - usually faster) `/index.php/new/<image_id>/<flavor_id>/<rounds>/nodns`

#### View the results  
* View all the results from all the tests and generate histograms : `/openstack-boottime/index.php/view` 
* Export the results in CSV : `/openstack-boottime/index.php/view/csv`

`<image_id>` and `<flavor_id>` are the specifications of the new VM that will be booted. You can get a list of your available options using the `$ nova image-list` and `$ nova flavor-list` commands from `python-novaclient`.


## Credit
Initial writing and commit by [Quentin Barrand](quentin.barrand@cern.ch), IT-SDC-OL, 2013 Summer Student.