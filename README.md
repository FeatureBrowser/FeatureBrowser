# FeatureBrowser
A tool that parses your Behat .feature files and turns them alchemy-like into an interactive user manual.

The Feature Browser tool generates .html files to represent your .feature files in a user-friendly format. Users can browse the generated manual by tag or directory, and view the features with all scenario outlines expanded.

## Configuration
@todo: Update the FB to look for the config file in the application's directory and read it. Right now it's limited to using the vendor version.
Configuring the FeatureBrowser can be done via a .yml file
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
php bin\featurebrowser generate
```

### Code Quality Scores
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/e3c45aee-65d9-4750-9a89-5916bc801cf8/mini.png)](https://insight.sensiolabs.com/projects/e3c45aee-65d9-4750-9a89-5916bc801cf8)

[![Code Climate](https://codeclimate.com/github/FeatureBrowser/FeatureBrowser/badges/gpa.svg)](https://codeclimate.com/github/FeatureBrowser/FeatureBrowser)
