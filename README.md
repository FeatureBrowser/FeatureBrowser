# FeatureBrowser
A tool that parses your Behat .feature files and turns them alchemy-like into an interactive user manual.

The Feature Browser tool generates .html files to represent your .feature files in a user-friendly format. Users can browse the generated manual by tag or directory, and view the features with all scenario outlines expanded.

### Contributing
Pull requests are very welcome, please base them off develop branch. Thank you!

## Installation
Install via composer/packagist:
```
composer require "feature-browser/feature-browser": "~1.0"
```

If you plan to use the tool only in your development environment and then deploy the generated documentation, rather than generating the files in your production environment, you can simply require dev only.
```
composer require --dev "feature-browser/feature-browser": "~1.0"
```

## Configuration
Configuring the FeatureBrowser can be done via the featurebrowser.yml file in your project root.
```
featurebrowser:
  project-name: 'My Project'
  base-url: 'myproject.com'
  output-directory: 'docs'
  features-directory: 'features'
```

## Usage
Run the featurebrowser generator from the command line:
```
php bin/featurebrowser generate
```

### Code Quality Scores
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/e3c45aee-65d9-4750-9a89-5916bc801cf8/mini.png)](https://insight.sensiolabs.com/projects/e3c45aee-65d9-4750-9a89-5916bc801cf8)

[![Code Climate](https://codeclimate.com/github/FeatureBrowser/FeatureBrowser/badges/gpa.svg)](https://codeclimate.com/github/FeatureBrowser/FeatureBrowser)

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/FeatureBrowser/FeatureBrowser/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/FeatureBrowser/FeatureBrowser/?branch=master)

[![DashboardHub Badge](http://dashboardhub.io/badge/5555d8e0283b52.76120295 "DashboardHub Badge")](http://dashboardhub.io/d/5555d8e0283b52.76120295)
