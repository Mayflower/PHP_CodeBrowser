{
  description = "A php test environment flake";

  outputs = { self, nixpkgs }:
    let
      system = "x86_64-linux";
      pkgs = nixpkgs.legacyPackages."${system}";

      phpEnv = phpPackage: (phpPackage.buildEnv {
        extensions = { enabled, all }: (enabled ++ [ all.xdebug ]);
        extraConfig = ''
          memory_limit=-1
          xdebug.mode=coverage
        '' +
        pkgs.lib.optionalString (pkgs.lib.versionOlder phpPackage.version "8.0") ''
          xdebug.coverage_enable=1
        '';
      });

      phpVersions = [
        "php73"
        "php74"
        "php80"
      ];
    in
    {
      packages."${system}" = builtins.listToAttrs
        (builtins.map
          (name:
            {
              name = "env-${name}";
              value = pkgs.symlinkJoin {
                name = "env-${name}";
                paths = [
                  (phpEnv pkgs."${name}")
                  (phpEnv pkgs."${name}").packages.composer2
                ];
              };
            }
          )
          phpVersions);
    };
}
