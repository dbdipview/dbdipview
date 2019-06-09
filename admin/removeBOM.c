/* 
 * Boris Domajnko: delete 3 bytes - UTF-8 BOM
 * Compile as:  cc -o removeBOM removeBOM.c
 * s/^\xEF\xBB\xBF/
 */
 
#include <stdio.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
 
void pHelp(char *me) {
  fprintf(stdout, "Removes first 3 bytes from a file: UTF-8 BOM marker!\n");
  fprintf(stdout, "Usage: %s <srcfile> <destfile>\n", me);
  exit(0);
}
 
int main(int argc, char *argv[]) {
  int inF, ouF;
  char line[512];
  int bytes;
 
  if(argc < 3)
    pHelp(argv[0]);
 
  if((inF = open(argv[1], O_RDONLY )) == -1) {
    perror("open");
    exit(-1);
  }
 
  if((ouF = open(argv[2], O_WRONLY | O_CREAT | O_TRUNC , 0700 )) == -1) {
    perror("open");
    exit(-1);
  }
 
  read(inF, line, 3);
  while((bytes = read(inF, line, sizeof(line))) > 0)
    write(ouF, line, bytes);
 
  close(inF);
  close(ouF);
}


