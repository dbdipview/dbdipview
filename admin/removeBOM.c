/* 
 * Delete 3 bytes from UTF-8 BOM file
 * Compile as:  cc -o removeBOM removeBOM.c
 * s/^\xEF\xBB\xBF/
 */
 
#include <stdio.h>
#include <stdlib.h>
#include <sys/stat.h>
 
void pHelp(char *me) {
  fprintf(stdout, "Removes first 3 bytes from a file: UTF-8 BOM marker!\n");
  fprintf(stdout, "Usage: %s <srcfile> <destfile>\n", me);
  exit(0);
}
 
int main(int argc, char *argv[]) {
  char c;

  char bom[3] = {0xEF, 0xBB, 0xBF};

  if(argc < 3)
    pHelp(argv[0]);
 
  FILE *inF = fopen(argv[1], "rb");
  if (inF == NULL) {
    perror("Error input file");
    exit(1);
  }

  FILE *outF = fopen(argv[2], "wb");
  if (outF == NULL) {
    perror("Error output file");
    exit(1);
  }

  fchmod(fileno(outF), 0600);

  int nread = fread(bom, 1, 3, inF);
  if (nread == 3 && bom[0] == 0xEF && bom[1] == 0xBB && bom[2] == 0xBF) {
    fseek(inF, 3, SEEK_SET);
  }

  while ((c = fgetc(inF)) != EOF) {
    fputc(c, outF);
  }

  fclose(inF);
  fclose(outF);
}

