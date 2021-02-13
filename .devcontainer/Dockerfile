ARG VARIANT="20.04"
FROM mcr.microsoft.com/vscode/devcontainers/base:0-ubuntu${VARIANT}

##
# Oh My Zsh Configuration
##

COPY p10k.zsh /root/.p10k.zsh
COPY p10k.zsh /home/vscode/.p10k.zsh

RUN git clone --depth=1 https://github.com/romkatv/powerlevel10k.git ${ZSH_CUSTOM:-~/.oh-my-zsh/custom}/themes/powerlevel10k \
  && su vscode -c "git clone --depth=1 https://github.com/romkatv/powerlevel10k.git ${ZSH_CUSTOM:-~/.oh-my-zsh/custom}/themes/powerlevel10k" \
  && echo '[[ ! -f ~/.p10k.zsh ]] || source ~/.p10k.zsh' >> ~/.zshrc \
  && su vscode -c "echo '[[ ! -f ~/.p10k.zsh ]] || source ~/.p10k.zsh' >> ~/.zshrc" \
  && sed -i 's#robbyrussell#powerlevel10k/powerlevel10k#' ~/.zshrc \
  && su vscode -c "sed -i 's#robbyrussell#powerlevel10k/powerlevel10k#' ~/.zshrc" \
  && chsh -s /bin/zsh root \
  && chsh -s /bin/zsh vscode

##
# Application Packages
##

RUN apt-get update \
  && export DEBIAN_FRONTEND=noninteractive \
  && apt-get -y install --no-install-recommends composer git php-curl php-zip php-xml unzip zip
